<?php

namespace App\Services\Sandbox;

use App\Models\Demos\Admin;
use App\Models\Central\DemoAccount;
use App\Models\Central\Tenant;
use App\Models\Demos\Client;
use App\Models\Demos\Seller;
use App\Support\SandboxDatabase;
use App\Support\SandboxWorkspace;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EnterDemoWorkspaceService
{
    public function enter(DemoAccount $account, string $role = 'admin'): string
    {
        $tenant = $account->sandboxTenant ?: Tenant::query()->find($account->tenant_id);

        if (! $tenant || ! SandboxWorkspace::isSandboxTenant($tenant)) {
            $tenant = app(EnsureSandboxTenantService::class)->ensure();
            app(SeedDemoWorkspaceService::class)->seed($tenant);
            $account->update(['tenant_id' => $tenant->id]);
        } else {
            app(SeedDemoWorkspaceService::class)->seed($tenant);
        }

        $this->forgetCrmGuards();

        TenantContext::set((int) $tenant->id);
        session([
            'tenant_id'         => (int) $tenant->id,
            'demo_account_id'   => $account->id,
            'demo_sandbox_role' => $role,
            'role'              => $role,
        ]);

        return SandboxDatabase::using(fn () => match ($role) {
            'seller' => $this->loginSeller($tenant),
            'client' => $this->loginClient($tenant),
            default  => $this->loginAdmin($tenant),
        });
    }

    public function exit(): void
    {
        $this->forgetCrmGuards();
        Auth::guard('demo')->logout();
        session()->forget(['demo_account_id', 'demo_sandbox_role', 'tenant_id', 'role']);
        TenantContext::clear();
    }

    private function loginAdmin(Tenant $tenant): string
    {
        $admin = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', (string) config('sandbox.admin_email'))
            ->first();

        if (! $admin) {
            throw new RuntimeException('Sandbox admin is not provisioned.');
        }

        Auth::guard('admin')->login($admin);
        session(['demo_sandbox_role' => 'admin', 'role' => 'admin']);

        return route('admin.index.get');
    }

    private function loginSeller(Tenant $tenant): string
    {
        $seller = Seller::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', (string) config('sandbox.seller_email'))
            ->first();

        if (! $seller) {
            throw new RuntimeException('Sandbox seller is not provisioned.');
        }

        Auth::guard('seller')->login($seller);
        session(['demo_sandbox_role' => 'seller', 'role' => 'seller']);

        return route('seller.index.get');
    }

    private function loginClient(Tenant $tenant): string
    {
        $client = Client::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', (string) config('sandbox.client_email'))
            ->first();

        if (! $client) {
            throw new RuntimeException('Sandbox client is not provisioned.');
        }

        Auth::guard('client')->login($client);
        session(['demo_sandbox_role' => 'client', 'role' => 'client']);

        return route('client.index.get');
    }

    private function forgetCrmGuards(): void
    {
        foreach (['tenant', 'admin', 'seller', 'client'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }
    }
}
