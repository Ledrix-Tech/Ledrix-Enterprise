<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalizes_pakistan_national_and_trunk_prefix(): void
    {
        $this->assertSame('+923001234567', PhoneNumber::normalize('3001234567', 'PK'));
        $this->assertSame('+923001234567', PhoneNumber::normalize('03001234567', 'PK'));
        $this->assertSame('+923001234567', PhoneNumber::normalize('+92 300 1234567', 'PK'));
        $this->assertSame('+923001234567', PhoneNumber::normalize('+03001234567', 'PK'));
    }

    public function test_keeps_valid_e164(): void
    {
        $this->assertSame('+14155552671', PhoneNumber::normalize('+14155552671', 'US'));
        $this->assertTrue(PhoneNumber::isValidE164('+923001234567'));
        $this->assertFalse(PhoneNumber::isValidE164('+03001234567'));
        $this->assertFalse(PhoneNumber::isValidE164('3001234567'));
    }
}
