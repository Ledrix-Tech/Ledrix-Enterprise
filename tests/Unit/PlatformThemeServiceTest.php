<?php

namespace Tests\Unit;

use App\Services\Platform\PlatformThemeService;
use PHPUnit\Framework\TestCase;

class PlatformThemeServiceTest extends TestCase
{
    public function test_sanitizes_and_derives_contrast(): void
    {
        $service = new PlatformThemeService;

        $this->assertSame('#c93700', $service->sanitizeHex('C93700'));
        $this->assertSame('#c93700', $service->sanitizeHex('#c93700'));
        $this->assertNull($service->sanitizeHex('red'));

        $this->assertSame('#ffffff', $service->contrastText('#c93700'));
        $this->assertSame('#111827', $service->contrastText('#f5c542'));

        $dark = $service->adjustBrightness('#c93700', -0.28);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $dark);
        $this->assertNotSame('#c93700', $dark);
    }
}
