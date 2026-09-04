<?php

namespace Tests\Unit;

use App\Mail\PaymentLinkCreated;
use App\Services\PaymentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesPaymentFlow;
use Tests\Support\CreatesPortalUsers;
use Tests\TestCase;

class PaymentLinkClientEmailTest extends TestCase
{
    use CreatesPaymentFlow;
    use CreatesPortalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTenantFeaturesEnabled();
    }

    public function test_email_client_sends_payment_link_immediately(): void
    {
        Mail::fake();

        ['brand' => $brand, 'lead' => $lead, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $link = app(PaymentLinkService::class)->createInstallmentLink(
            brand: $brand,
            lead: $lead,
            sellerIdWhoGenerated: $seller->id,
            serviceName: 'Website Redesign',
            currency: 'USD',
            totalCents: 10_000,
            payNowCents: 10_000,
            provider: 'stripe',
        );

        $url = 'https://ledrix.co/pay/now/test-token';
        $sent = app(PaymentLinkService::class)->emailClient($link, $url);

        $this->assertTrue($sent);
        Mail::assertSent(PaymentLinkCreated::class, function (PaymentLinkCreated $mail) use ($lead, $url) {
            return $mail->hasTo($lead->email)
                && $mail->url === $url
                && $mail->service === 'Website Redesign'
                && str_contains($mail->amount, 'USD');
        });
    }

    public function test_email_client_falls_back_to_client_email(): void
    {
        Mail::fake();

        ['brand' => $brand, 'lead' => $lead, 'seller' => $seller, 'client' => $client] = $this->createPaymentLeadGraph();

        $link = app(PaymentLinkService::class)->createInstallmentLink(
            brand: $brand,
            lead: $lead,
            sellerIdWhoGenerated: $seller->id,
            serviceName: 'Website Redesign',
            currency: 'USD',
            totalCents: 10_000,
            payNowCents: 10_000,
            provider: 'stripe',
        );

        $link->loadMissing(['brand', 'lead', 'client']);
        $link->lead->email = '';

        $sent = app(PaymentLinkService::class)->emailClient($link, 'https://ledrix.co/pay/now/test-token');

        $this->assertTrue($sent);
        Mail::assertSent(PaymentLinkCreated::class, function (PaymentLinkCreated $mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }
}
