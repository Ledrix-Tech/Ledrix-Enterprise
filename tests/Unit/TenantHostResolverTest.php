<?php

namespace Tests\Unit;

use App\Support\TenantHostResolver;
use Tests\TestCase;

class TenantHostResolverTest extends TestCase
{
    public function test_ip_app_url_builds_localhost_workspace_links(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $this->assertSame('tronix.localhost', TenantHostResolver::workspaceHostForSlug('tronix'));
        $this->assertSame('http://tronix.localhost:8000', TenantHostResolver::workspaceBaseUrlForSlug('tronix'));
        $this->assertTrue(TenantHostResolver::isPlatformHost('tronix.localhost'));
    }

    public function test_production_app_url_keeps_slug_subdomain(): void
    {
        config(['app.url' => 'https://ledrix.co']);

        $this->assertSame('tronix.ledrix.co', TenantHostResolver::workspaceHostForSlug('tronix'));
        $this->assertSame('https://tronix.ledrix.co', TenantHostResolver::workspaceBaseUrlForSlug('tronix'));
    }
}
