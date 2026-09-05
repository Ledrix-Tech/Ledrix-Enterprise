<?php

namespace Tests\Unit;

use App\Support\Countries;
use Tests\TestCase;

class CountriesTest extends TestCase
{
    public function test_common_registration_countries_are_listed(): void
    {
        $this->assertTrue(Countries::isValid('PK'));
        $this->assertTrue(Countries::isValid('ae'));
        $this->assertSame('Pakistan', Countries::name('pk'));
        $this->assertContains('US', Countries::codes());
        $this->assertFalse(Countries::isValid('XX'));
    }
}
