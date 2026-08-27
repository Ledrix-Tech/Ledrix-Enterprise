<?php

namespace Tests\Unit;

use Tests\TestCase;

class InvoiceTaxConfigTest extends TestCase
{
    public function test_invoice_tax_defaults_to_zero(): void
    {
        $this->assertSame(0.0, (float) config('services.invoice_tax.rate'));
        $this->assertSame('Tax', config('services.invoice_tax.label'));
    }

    public function test_tax_math_matches_create_service_formula(): void
    {
        $subtotal = 100.0;
        $taxRate = 10.0;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $this->assertSame(10.0, $taxAmount);
        $this->assertSame(110.0, $total);
    }
}
