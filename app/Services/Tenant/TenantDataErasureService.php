<?php

namespace App\Services\Tenant;

use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Models\Central\TenantMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class TenantDataErasureService
{
    public const CONFIRM_PHRASE = 'ERASE';

    /** CRM tables (primary) with contact PII scoped by tenant_id. */
    private const CRM_PII_TABLES = [
        'leads',
        'clients',
        'sellers',
        'admins',
    ];

    /** Never hard-delete these; only blank party columns when present. */
    private const BILLING_TABLES = [
        'tenant_invoices',
        'tenant_payments',
    ];

    private const PARTY_COLUMNS = [
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'customer_name',
        'customer_email',
        'customer_phone',
        'payer_name',
        'payer_email',
        'payer_phone',
    ];

    private const SECRET_COLUMNS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_secret',
        'paypal_webhook_id',
        'plain_token',
        'token',
        'api_token',
    ];

    private const META_PII_KEYS = [
        'registered_from',
        'landing_path',
        'attribution',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer',
        'gclid',
        'fbclid',
        'msclkid',
        'marketing',
    ];

    public function __construct(
        private readonly TenantLifecycleService $lifecycle,
    ) {}

    /**
     * Irreversible GDPR erasure: anonymize PII, revoke access, soft-offboard.
     * Billing invoice/payment rows are retained (party fields blanked when present).
     */
    public function erase(
        Tenant $tenant,
        string $reason,
        string $actorType = 'super_admin',
        ?int $actorId = null,
        ?string $actorName = null,
    ): Tenant {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('An erasure reason is required.');
        }

        $tenant = Tenant::withTrashed()->findOrFail($tenant->id);

        if ($this->alreadyErased($tenant)) {
            throw new InvalidArgumentException('This tenant has already been erased.');
        }

        $before = [
            'name'   => $tenant->name,
            'email'  => $tenant->email,
            'phone'  => $tenant->phone ?? null,
            'status' => $tenant->status,
        ];

        $this->anonymizeCrmTables((int) $tenant->id);
        $this->anonymizeBillingPartyFields((int) $tenant->id);

        if (! $tenant->trashed()) {
            $this->lifecycle->offboard(
                $tenant,
                'GDPR erasure: '.$reason,
                $actorType,
                $actorId,
                $actorName,
            );
            $tenant = Tenant::withTrashed()->findOrFail($tenant->id);
        } else {
            $this->revokeAndCancelViaLifecyclePatterns($tenant, $reason);
        }

        $this->anonymizeTenantRow($tenant, $reason);
        $tenant->refresh();

        AuditLog::record(
            'tenant.erasure_completed',
            (int) $tenant->id,
            $actorType,
            $actorId,
            $actorName,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'GDPR erasure completed: '.$reason,
                'before'       => $before,
                'after'        => [
                    'name'       => $tenant->name,
                    'email'      => $tenant->email,
                    'status'     => $tenant->status,
                    'deleted_at' => $tenant->deleted_at?->toIso8601String(),
                ],
            ]
        );

        return $tenant;
    }

    public static function confirmPhraseMatches(string $input): bool
    {
        return trim($input) === self::CONFIRM_PHRASE;
    }

    private function alreadyErased(Tenant $tenant): bool
    {
        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        if (! empty($meta['erasure_completed_at'])) {
            return true;
        }

        $email = (string) $tenant->email;

        return str_ends_with($email, '@erased.local');
    }

    private function anonymizeTenantRow(Tenant $tenant, string $reason): void
    {
        $id = (int) $tenant->id;
        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        $meta = $this->scrubMetaPii($meta);
        $meta['erasure_completed_at'] = now()->toIso8601String();
        $meta['erasure_reason'] = $reason;

        $payload = [
            'name'  => 'Erased Tenant',
            'email' => 'erased+'.$id.'@erased.local',
            'meta'  => $meta,
            'status' => 'cancelled',
            'suspended_reason' => 'GDPR erasure: '.$reason,
            'suspended_at' => $tenant->suspended_at ?? now(),
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'registered_ip' => null,
            'last_login_ip' => null,
            'last_login_at' => null,
            'email_verified_at' => null,
            'website' => null,
            'address' => null,
            'logo' => null,
        ];

        foreach (['phone', 'billing_name', 'billing_email', 'billing_phone', 'billing_address'] as $col) {
            if (Schema::connection('central')->hasColumn('tenants', $col)) {
                $payload[$col] = null;
            }
        }

        foreach ([
            'two_factor_secret',
            'two_factor_recovery_codes',
            'jazzcash_payment_token',
            'stripe_setup_intent_id',
            'stripe_customer_id',
            'stripe_payment_method_id',
        ] as $col) {
            if (Schema::connection('central')->hasColumn('tenants', $col)) {
                $payload[$col] = null;
            }
        }

        $tenant->forceFill($payload)->save();
    }

    private function scrubMetaPii(array $meta): array
    {
        foreach (self::META_PII_KEYS as $key) {
            unset($meta[$key]);
        }

        return $meta;
    }

    private function anonymizeCrmTables(int $tenantId): void
    {
        foreach (self::CRM_PII_TABLES as $table) {
            if (! Schema::connection('primary')->hasTable($table)) {
                continue;
            }
            if (! Schema::connection('primary')->hasColumn($table, 'tenant_id')) {
                continue;
            }

            $query = DB::connection('primary')->table($table)->where('tenant_id', $tenantId);

            $query->orderBy('id')->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $this->anonymizeCrmRow($table, (array) $row);
                }
            });
        }
    }

    private function anonymizeCrmRow(string $table, array $row): void
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            return;
        }

        $update = [];

        if (Schema::connection('primary')->hasColumn($table, 'name')) {
            $update['name'] = 'Erased';
        }
        if (Schema::connection('primary')->hasColumn($table, 'email')) {
            $update['email'] = 'erased+'.$table.'-'.$id.'@erased.local';
        }
        if (Schema::connection('primary')->hasColumn($table, 'phone')) {
            $update['phone'] = null;
        }

        foreach (self::SECRET_COLUMNS as $col) {
            if (Schema::connection('primary')->hasColumn($table, $col)) {
                $update[$col] = $col === 'password' ? Hash::make(Str::random(64)) : null;
            }
        }

        if (Schema::connection('primary')->hasColumn($table, 'remember_token')) {
            $update['remember_token'] = null;
        }

        if (Schema::connection('primary')->hasColumn($table, 'deleted_at') && empty($row['deleted_at'])) {
            $update['deleted_at'] = now();
        }

        if (Schema::connection('primary')->hasColumn($table, 'updated_at')) {
            $update['updated_at'] = now();
        }

        if ($update === []) {
            return;
        }

        DB::connection('primary')->table($table)->where('id', $id)->update($update);
    }

    private function anonymizeBillingPartyFields(int $tenantId): void
    {
        foreach (self::BILLING_TABLES as $table) {
            if (! Schema::connection('central')->hasTable($table)) {
                continue;
            }
            if (! Schema::connection('central')->hasColumn($table, 'tenant_id')) {
                continue;
            }

            $columns = array_values(array_filter(
                self::PARTY_COLUMNS,
                fn (string $col) => Schema::connection('central')->hasColumn($table, $col)
            ));

            $update = [];
            foreach ($columns as $col) {
                $update[$col] = null;
            }
            if (Schema::connection('central')->hasColumn($table, 'notes')) {
                $update['notes'] = null;
            }
            if (Schema::connection('central')->hasColumn($table, 'updated_at')) {
                $update['updated_at'] = now();
            }

            if ($update === []) {
                continue;
            }

            DB::connection('central')
                ->table($table)
                ->where('tenant_id', $tenantId)
                ->update($update);
        }
    }

    /**
     * When tenant is already soft-deleted, still revoke tokens / cancel memberships.
     */
    private function revokeAndCancelViaLifecyclePatterns(Tenant $tenant, string $reason): void
    {
        DB::connection('central')->transaction(function () use ($tenant, $reason) {
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

            if (Schema::connection('central')->hasTable('tenant_memberships')) {
                TenantMembership::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereNotIn('status', ['cancelled', 'expired'])
                    ->update([
                        'status'        => 'cancelled',
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'GDPR erasure: '.$reason,
                    ]);
            }
        });
    }
}
