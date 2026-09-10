<?php

namespace App\Services\Sandbox;

use App\Models\Central\DemoAccount;
use App\Models\Central\Tenant;
use App\Support\SandboxWorkspace;
use Illuminate\Support\Facades\Hash;

class RegisterDemoAccountService
{
    public function __construct(
        private EnsureSandboxTenantService $sandboxTenant,
        private SeedDemoWorkspaceService $workspace,
    ) {}

    /**
     * Register a visitor into demo_accounts and attach them to the shared sandbox.
     * Does not create a tenant, membership trial, or consume trial_used.
     */
    public function register(array $data, ?string $ip = null): DemoAccount
    {
        $tenant = $this->sandboxTenant->ensure();
        $this->workspace->seed($tenant);

        return DemoAccount::query()->create(SandboxWorkspace::fillableOn('demo_accounts', [
            'name'          => $data['name'],
            'email'         => strtolower(trim((string) $data['email'])),
            'password'      => Hash::make($data['password']),
            'company'       => $data['company'] ?? null,
            'tenant_id'     => $tenant->id,
            'status'        => 'active',
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'meta'          => [
                'source' => 'sandbox_register',
            ],
        ]));
    }

    public function sandboxTenant(): Tenant
    {
        $tenant = $this->sandboxTenant->ensure();
        $this->workspace->seed($tenant);

        return $tenant;
    }
}
