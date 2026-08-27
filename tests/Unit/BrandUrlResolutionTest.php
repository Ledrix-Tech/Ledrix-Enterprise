<?php

namespace Tests\Unit;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandUrlResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_by_url_path_prefers_longest_prefix(): void
    {
        $brandA = Brand::factory()->create([
            'brand_url'  => 'https://ledrix.co/projects/craftic-designs/contact',
            'brand_host' => 'ledrix.co',
        ]);
        $brandB = Brand::factory()->create([
            'brand_url'  => 'https://ledrix.co/projects/craftic-designs-all/contact',
            'brand_host' => 'ledrix.co',
        ]);

        $resolved = Brand::resolveByHostKey(
            'ledrix.co',
            'https://ledrix.co/projects/craftic-designs-all/contact'
        );

        $this->assertNotNull($resolved);
        $this->assertSame($brandB->id, $resolved->id);
        $this->assertNotSame($brandA->id, $resolved->id);
    }

    public function test_script_route_key_is_unique_when_host_is_shared(): void
    {
        $brandA = Brand::factory()->create([
            'brand_url'  => 'https://ledrix.co/projects/a',
            'brand_host' => 'ledrix.co',
        ]);
        $brandB = Brand::factory()->create([
            'brand_url'  => 'https://ledrix.co/projects/b',
            'brand_host' => 'ledrix.co',
        ]);

        $this->assertSame('brand-' . $brandA->id, $brandA->scriptRouteKey());
        $this->assertSame('brand-' . $brandB->id, $brandB->scriptRouteKey());
    }
}
