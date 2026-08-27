<?php

namespace Tests\Feature;

use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * F-20: Disabled SSO routes return 404; SCIM stub returns 501.
 * Avoids RefreshDatabase so tests run without a local MySQL primary DB.
 */
class SsoDisabledTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();

        config([
            'sso.enabled' => false,
            'sso.issuer_url' => null,
            'sso.client_id' => null,
            'sso.client_secret' => null,
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');
    }

    public function test_super_admin_sso_redirect_returns_404_when_disabled(): void
    {
        $this->get(route('super-admin.sso.redirect'))->assertNotFound();
    }

    public function test_super_admin_sso_callback_returns_404_when_disabled(): void
    {
        $this->get(route('super-admin.sso.callback', [
            'code' => 'x',
            'state' => 'y',
        ]))->assertNotFound();
    }

    public function test_admin_sso_redirect_returns_404_when_disabled(): void
    {
        $this->get(route('admin.sso.redirect'))->assertNotFound();
    }

    public function test_admin_sso_callback_returns_404_when_disabled(): void
    {
        $this->get(route('admin.sso.callback', [
            'code' => 'x',
            'state' => 'y',
        ]))->assertNotFound();
    }

    public function test_scim_returns_404_when_disabled(): void
    {
        config(['scim.enabled' => false]);

        $this->getJson('/api/scim/v2/Users')
            ->assertNotFound();
    }
}
