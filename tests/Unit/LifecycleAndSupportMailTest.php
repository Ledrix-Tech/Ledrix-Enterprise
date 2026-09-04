<?php

namespace Tests\Unit;

use App\Mail\PlatformSupportOpsMail;
use App\Mail\TenantInvoiceVoidedMail;
use App\Mail\TenantPaymentRefundedMail;
use App\Mail\TenantSuspendedMail;
use App\Models\Central\PlatformSupportTicket;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Models\ClientTicket;
use App\Notifications\TicketCreatedNotification;
use App\Services\Billing\RefundTenantPaymentService;
use App\Services\Billing\VoidTenantInvoiceService;
use App\Support\SafeMail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class LifecycleAndSupportMailTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureBillingTables();
        $this->ensureSupportTables();
        config(['services.bank_transfer.notify_email' => 'ops@example.com']);
    }

    public function test_voiding_issued_invoice_emails_tenant(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant('void-co');

        $invoice = TenantInvoice::query()->create([
            'tenant_id'      => $tenant->id,
            'invoice_number' => 'LDX-2026-0099',
            'plan_name'      => 'Agency',
            'billing_cycle'  => 'monthly',
            'amount'         => 99,
            'currency'       => 'USD',
            'tax_amount'     => 0,
            'total_amount'   => 99,
            'status'         => 'issued',
            'issued_at'      => now(),
        ]);

        $result = app(VoidTenantInvoiceService::class)->void($invoice, 'Duplicate charge');

        $this->assertSame('void', $result->status);

        Mail::assertSent(TenantInvoiceVoidedMail::class, function (TenantInvoiceVoidedMail $mail) use ($tenant) {
            return $mail->hasTo($tenant->email)
                && $mail->reason === 'Duplicate charge'
                && $mail->invoice->invoice_number === 'LDX-2026-0099';
        });
    }

    public function test_refunding_paid_payment_emails_tenant(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant('refund-co');

        $payment = TenantPayment::query()->create([
            'tenant_id'       => $tenant->id,
            'transaction_id'  => 'txn_refund_'.uniqid(),
            'gateway'         => 'manual',
            'amount'          => 50,
            'refunded_amount' => 0,
            'currency'        => 'USD',
            'refund_status'   => 'none',
            'status'          => 'paid',
            'paid_at'         => now(),
            'payload'         => [],
        ]);

        $result = app(RefundTenantPaymentService::class)->refund(
            $payment,
            reason: 'Goodwill credit',
        );

        $this->assertSame(50.0, $result['credit_applied']);
        $this->assertFalse($result['stripe_refunded']);

        Mail::assertSent(TenantPaymentRefundedMail::class, function (TenantPaymentRefundedMail $mail) use ($tenant) {
            return $mail->hasTo($tenant->email)
                && $mail->refundAmount === 50.0
                && $mail->creditApplied === 50.0
                && $mail->reason === 'Goodwill credit';
        });
    }

    public function test_support_ops_mail_goes_to_ops_inbox(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant('ticket-co');

        $ticket = PlatformSupportTicket::query()->create([
            'tenant_id'   => $tenant->id,
            'subject'     => 'Cannot download invoice',
            'description' => 'PDF button 500s',
            'category'    => 'billing',
            'priority'    => 'high',
            'status'      => 'open',
        ]);
        $ticket->setRelation('tenant', $tenant);

        SafeMail::toOps(
            fn () => new PlatformSupportOpsMail($ticket, 'created'),
            'Support ticket opened mail',
        );
        SafeMail::toOps(
            fn () => new PlatformSupportOpsMail($ticket, 'tenant_replied', 'Still broken on Chrome.'),
            'Support ticket tenant reply mail',
        );

        Mail::assertSent(PlatformSupportOpsMail::class, 2);
        Mail::assertSent(PlatformSupportOpsMail::class, function (PlatformSupportOpsMail $mail) {
            return $mail->hasTo('ops@example.com')
                && $mail->event === 'created'
                && $mail->ticket->subject === 'Cannot download invoice';
        });
        Mail::assertSent(PlatformSupportOpsMail::class, function (PlatformSupportOpsMail $mail) {
            return $mail->event === 'tenant_replied'
                && $mail->replyMessage === 'Still broken on Chrome.';
        });
    }

    public function test_safe_mail_skips_invalid_addresses_and_swallows_failures(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant('safe-mail');

        SafeMail::send('not-an-email', fn () => new TenantSuspendedMail($tenant, 'x'));
        Mail::assertNothingSent();

        try {
            SafeMail::send('ops@example.com', function () {
                throw new \RuntimeException('smtp down');
            }, 'explode mail');
        } catch (\Throwable $e) {
            $this->fail('SafeMail must not throw: '.$e->getMessage());
        }

        Mail::assertNothingSent();
    }

    public function test_safe_mail_swallows_mailable_send_exceptions(): void
    {
        try {
            SafeMail::send('ops@example.com', new class extends Mailable
            {
                public function envelope(): Envelope
                {
                    throw new \RuntimeException('render failed');
                }

                public function content(): Content
                {
                    return new Content(htmlString: 'x');
                }
            }, 'explode send');
        } catch (\Throwable $e) {
            $this->fail('SafeMail must not throw: '.$e->getMessage());
        }

        $this->addToAssertionCount(1);
    }

    public function test_safe_mail_notify_sends_immediately_and_swallows_failures(): void
    {
        Mail::fake();
        Notification::fake();

        $ok = SafeMail::notify('ops@example.com', new TicketCreatedNotification(
            new ClientTicket(['id' => 1])
        ), 'notify test');

        $this->assertTrue($ok);
        Notification::assertSentOnDemand(\App\Notifications\TicketCreatedNotification::class);

        try {
            $skipped = SafeMail::notify('not-an-email', new TicketCreatedNotification(
                new ClientTicket(['id' => 2])
            ), 'notify skip');
        } catch (\Throwable $e) {
            $this->fail('SafeMail::notify must not throw: '.$e->getMessage());
        }

        $this->assertFalse($skipped);
    }

    private function makeTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name'     => ucfirst($slug),
            'slug'     => $slug,
            'email'    => $slug.'@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);
    }

    private function ensureBillingTables(): void
    {
        if (! Schema::connection('central')->hasTable('tenant_invoices')) {
            Schema::connection('central')->create('tenant_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('membership_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->string('plan_name')->nullable();
                $table->string('billing_cycle')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('status')->default('issued');
                $table->string('pdf_path')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_payments')) {
            Schema::connection('central')->create('tenant_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('membership_id')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('transaction_id')->unique();
                $table->string('payment_intent_id')->nullable();
                $table->string('gateway')->default('manual');
                $table->string('order_type')->default('new');
                $table->string('renewed_by')->nullable();
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('refunded_amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('refund_status')->default('none');
                $table->string('status')->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensureSupportTables(): void
    {
        if (! Schema::connection('central')->hasTable('platform_support_tickets')) {
            Schema::connection('central')->create('platform_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->string('subject');
                $table->longText('description');
                $table->string('category')->default('other');
                $table->string('priority')->default('medium');
                $table->string('status')->default('open');
                $table->timestamp('first_replied_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('platform_support_replies')) {
            Schema::connection('central')->create('platform_support_replies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id');
                $table->string('sender_type');
                $table->unsignedBigInteger('sender_id');
                $table->longText('message');
                $table->string('attachment_path')->nullable();
                $table->boolean('is_internal')->default(false);
                $table->timestamps();
            });
        }
    }
}
