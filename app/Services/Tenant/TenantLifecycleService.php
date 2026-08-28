<?php

namespace App\Services\Tenant;

use App\Mail\TenantSuspendedMail;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Models\Central\TenantMembership;
use App\Support\SafeMail;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class TenantLifecycleService
{
    /**
     * Suspend a tenant: set status + metadata, revoke API tokens, end open memberships.
     */
    public function suspend(
        Tenant $tenant,
        string $reason,
        string $actorType = 'super_admin',
        ?int $actorId = null,
        ?string $actorName = null,
    ): Tenant {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A suspend reason is required.');
        }

        if ($tenant->trashed()) {
            throw new InvalidArgumentException('Cannot suspend an offboarded tenant. Restore it first.');
        }

        $before = [
            'status'           => $tenant->status,
            'suspended_reason' => $tenant->suspended_reason,
            'suspended_at'     => $tenant->suspended_at?->toIso8601String(),
        ];

        DB::connection('central')->transaction(function () use ($tenant, $reason) {
            $tenant->suspend($reason);
            $this->revokeApiTokens($tenant);
            $this->markMembershipsPastDue($tenant);
        });

        $tenant->refresh();

        AuditLog::record(
            'tenant.suspended',
            (int) $tenant->id,
            $actorType,
            $actorId,
            $actorName,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'Tenant suspended: '.$reason,
                'before'       => $before,
                'after'        => [
                    'status'           => $tenant->status,
                    'suspended_reason' => $tenant->suspended_reason,
                    'suspended_at'     => $tenant->suspended_at?->toIso8601String(),
                ],
            ]
        );

        SafeMail::send(
            $tenant->email,
            fn () => new TenantSuspendedMail($tenant, $reason),
            'Tenant suspend mail',
            ['tenant_id' => $tenant->id],
        );

        return $tenant;
    }

    /**
     * Reactivate a suspended or inactive tenant (not soft-deleted).
     */
    public function activate(
        Tenant $tenant,
        string $actorType = 'super_admin',
        ?int $actorId = null,
        ?string $actorName = null,
    ): Tenant {
        if ($tenant->trashed()) {
            throw new InvalidArgumentException('Cannot activate an offboarded tenant. Use restore first.');
        }

        $before = [
            'status'           => $tenant->status,
            'suspended_reason' => $tenant->suspended_reason,
        ];

        $tenant->activate();
        $tenant->refresh();

        AuditLog::record(
            'tenant.activated',
            (int) $tenant->id,
            $actorType,
            $actorId,
            $actorName,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'Tenant activated',
                'before'       => $before,
                'after'        => ['status' => $tenant->status],
            ]
        );

        return $tenant;
    }

    /**
     * Soft-offboard: cancel status, revoke tokens, soft-delete tenant row.
     * Billing history (invoices/payments) is retained.
     */
    public function offboard(
        Tenant $tenant,
        string $reason,
        string $actorType = 'super_admin',
        ?int $actorId = null,
        ?string $actorName = null,
    ): Tenant {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('An offboard reason is required.');
        }

        if ($tenant->trashed()) {
            throw new InvalidArgumentException('Tenant is already offboarded.');
        }

        $before = [
            'status' => $tenant->status,
            'email'  => $tenant->email,
        ];

        DB::connection('central')->transaction(function () use ($tenant, $reason) {
            $this->revokeApiTokens($tenant);
            $this->cancelMemberships($tenant);

            $tenant->forceFill([
                'status'           => 'cancelled',
                'suspended_reason' => $reason,
                'suspended_at'     => now(),
            ])->save();

            $tenant->delete(); // SoftDeletes
        });

        AuditLog::record(
            'tenant.offboarded',
            (int) $tenant->id,
            $actorType,
            $actorId,
            $actorName,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'Tenant offboarded: '.$reason,
                'before'       => $before,
                'after'        => [
                    'status'     => 'cancelled',
                    'deleted_at' => now()->toIso8601String(),
                ],
            ]
        );

        return $tenant->fresh() ?? $tenant;
    }

    /**
     * Restore a soft-deleted tenant to inactive (manual activate required).
     */
    public function restore(
        Tenant $tenant,
        string $actorType = 'super_admin',
        ?int $actorId = null,
        ?string $actorName = null,
    ): Tenant {
        if (! $tenant->trashed()) {
            throw new InvalidArgumentException('Tenant is not offboarded.');
        }

        $tenant->restore();
        $tenant->forceFill([
            'status'           => 'inactive',
            'suspended_reason' => null,
            'suspended_at'     => null,
        ])->save();

        AuditLog::record(
            'tenant.restored',
            (int) $tenant->id,
            $actorType,
            $actorId,
            $actorName,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'Offboarded tenant restored (inactive — activate to reopen access)',
                'after'        => ['status' => 'inactive'],
            ]
        );

        return $tenant->refresh();
    }

    private function revokeApiTokens(Tenant $tenant): void
    {
        TenantApiToken::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get()
            ->each(function (TenantApiToken $token) {
                try {
                    $token->revoke();
                } catch (Throwable) {
                    // continue
                }
            });
    }

    private function markMembershipsPastDue(Tenant $tenant): void
    {
        TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'past_due']);
    }

    private function cancelMemberships(Tenant $tenant): void
    {
        TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => 'Tenant offboarded by platform',
            ]);
    }
}
