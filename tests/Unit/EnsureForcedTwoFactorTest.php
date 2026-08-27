<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureForcedTwoFactor;
use App\Models\Central\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class EnsureForcedTwoFactorTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
    }

    public function test_owner_without_2fa_is_redirected_when_forced(): void
    {
        config(['security.force_super_admin_owner_2fa' => true]);

        $owner = SuperAdmin::query()->create([
            'name'     => 'Owner',
            'email'    => 'owner-2fa@example.com',
            'password' => Hash::make('Password1!'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);

        Auth::guard('super_admin')->login($owner);

        $request = Request::create('/super-admin/dashboard', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/super-admin/dashboard', fn () => null);
            $route->name('super-admin.index.get');

            return $route;
        });

        $response = (new EnsureForcedTwoFactor)->handle($request, fn () => response('ok'), 'super_admin');

        $this->assertTrue($response->isRedirect());
        $this->assertSame(route('super-admin.2fa.setup'), $response->headers->get('Location'));
    }

    public function test_owner_with_2fa_passes(): void
    {
        config(['security.force_super_admin_owner_2fa' => true]);

        $owner = SuperAdmin::query()->create([
            'name'             => 'Owner',
            'email'            => 'owner-2fa-ok@example.com',
            'password'         => Hash::make('Password1!'),
            'role'             => 'owner',
            'status'           => 'active',
            'two_factor_secret'=> 'BASE32SECRET',
        ]);

        Auth::guard('super_admin')->login($owner);

        $request = Request::create('/super-admin/dashboard', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/super-admin/dashboard', fn () => null);
            $route->name('super-admin.index.get');

            return $route;
        });

        $response = (new EnsureForcedTwoFactor)->handle($request, fn () => response('ok'), 'super_admin');

        $this->assertSame('ok', $response->getContent());
    }
}
