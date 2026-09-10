<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM tables for the shared sandbox tenant on ledrix_demos.
 * Not a copy of the full primary schema — only what the demo tour needs.
 */
return new class extends Migration
{
    protected $connection = 'demos_db';

    public function up(): void
    {
        Schema::connection('demos_db')->create('brands', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->enum('module', ['upwork', 'ppc'])->default('ppc');
            $t->string('brand_name');
            $t->string('brand_url');
            $t->string('brand_host')->nullable()->index();
            $t->json('allowed_origins')->nullable();
            $t->string('public_form_token')->nullable();
            $t->string('webhook_secret')->nullable();
            $t->boolean('require_hmac')->default(false);
            $t->longText('lead_script')->nullable();
            $t->json('field_mapping')->nullable();
            $t->enum('status', ['Pending', 'Active'])->default('Active');
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('admins', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->string('role')->default('admin');
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->timestamp('last_seen')->nullable();
            $t->longText('meta')->nullable();
            $t->rememberToken();
            $t->timestamps();
            $t->unique(['tenant_id', 'email']);
        });

        Schema::connection('demos_db')->create('clients', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->string('name');
            $t->string('email');
            $t->string('password')->nullable();
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->string('phone')->nullable();
            $t->json('meta')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->default('Active');
            $t->timestamp('last_seen')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['tenant_id', 'email']);
        });

        Schema::connection('demos_db')->create('sellers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $t->string('name');
            $t->string('sudo_name')->nullable();
            $t->enum('is_seller', ['project_manager', 'front_seller'])->index();
            $t->string('email');
            $t->string('password');
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->default('Active');
            $t->timestamp('last_seen')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['tenant_id', 'email']);
        });

        Schema::connection('demos_db')->create('leads', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $t->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('service')->nullable();
            $t->longText('message')->nullable();
            $t->enum('status', [
                'new', 'contacted', 'qualified', 'proposal_sent', 'first_paid',
                'in_progress', 'completed', 'renewal_due', 'on_hold', 'disqualified', 'cancelled',
            ])->default('new');
            $t->boolean('auto_replied')->default(false);
            $t->timestamp('converted_at')->nullable();
            $t->string('domain_url')->nullable();
            $t->json('prediction')->nullable();
            $t->json('meta')->nullable();
            $t->boolean('is_finish')->default(false);
            $t->string('source', 32)->nullable()->index();
            $t->unsignedBigInteger('import_batch_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['brand_id', 'seller_id', 'status', 'created_at']);
        });

        Schema::connection('demos_db')->create('lead_assignments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->unsignedBigInteger('assigned_to');
            $t->string('assigned_role');
            $t->unsignedBigInteger('assigned_by');
            $t->timestamp('assigned_at')->useCurrent();
            $t->enum('status', [
                'pending', 'assigned', 'in_progress', 'on_hold', 'completed',
                'refund_requested', 'chargeback', 'rejected_by_client', 'cancelled',
            ])->default('pending');
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::connection('demos_db')->create('orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->unsignedBigInteger('parent_order_id')->nullable();
            $t->enum('order_type', ['original', 'renewal'])->default('original');
            $t->string('service_name')->nullable();
            $t->string('currency', 3)->default('USD');
            $t->unsignedInteger('unit_amount');
            $t->unsignedInteger('amount_paid')->default(0);
            $t->unsignedInteger('balance_due')->default(0);
            $t->enum('status', [
                'draft', 'pending', 'paid', 'in_progress', 'revision',
                'completed', 'refunded', 'canceled',
            ])->default('draft');
            $t->unsignedBigInteger('front_seller_id')->nullable();
            $t->unsignedBigInteger('owner_seller_id')->nullable();
            $t->unsignedBigInteger('opened_by_seller_id')->nullable();
            $t->unsignedInteger('front_credits_used')->default(0);
            $t->unsignedBigInteger('front_credited_cents')->default(0);
            $t->timestamp('first_paid_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->unsignedInteger('refunded_amount')->default(0);
            $t->enum('refund_status', ['none', 'partial', 'full', 'chargeback'])->default('none');
            $t->string('provider_session_id')->nullable();
            $t->string('provider_payment_intent_id')->nullable();
            $t->string('buyer_name')->nullable();
            $t->string('buyer_email')->nullable();
            $t->json('meta')->nullable();
            $t->string('source', 32)->nullable();
            $t->unsignedBigInteger('import_batch_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::connection('demos_db')->create('payment_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $t->unsignedBigInteger('credit_to_seller_id')->nullable();
            $t->unsignedBigInteger('owner_seller_id')->nullable();
            $t->unsignedBigInteger('generated_by_id')->nullable();
            $t->string('generated_by_type', 30)->nullable();
            $t->string('service_name');
            $t->string('currency', 3)->default('USD');
            $t->enum('provider', ['stripe', 'paypal'])->default('stripe');
            $t->unsignedInteger('unit_amount');
            $t->unsignedInteger('order_total_snapshot')->nullable();
            $t->string('provider_session_id')->nullable();
            $t->string('provider_payment_intent_id')->nullable();
            $t->string('token')->unique();
            $t->enum('status', ['draft', 'active', 'paid', 'completed', 'canceled', 'expired'])->default('active');
            $t->string('expires_at')->nullable();
            $t->boolean('is_active_link')->default(true);
            $t->text('last_issued_url')->nullable();
            $t->timestamp('last_issued_at')->nullable();
            $t->timestamp('last_issued_expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->string('source', 32)->nullable();
            $t->unsignedBigInteger('import_batch_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::connection('demos_db')->create('payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('payment_link_id')->nullable()->constrained('payment_links')->nullOnDelete();
            $t->unsignedBigInteger('credit_to_seller_id')->nullable();
            $t->unsignedBigInteger('seller_id')->nullable();
            $t->unsignedBigInteger('owner_seller_id')->nullable();
            $t->unsignedBigInteger('front_seller_id')->nullable();
            $t->unsignedBigInteger('credited_seller_id')->nullable();
            $t->unsignedInteger('amount');
            $t->string('currency', 3)->default('USD');
            $t->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $t->string('provider')->default('stripe');
            $t->string('provider_payment_intent_id')->index();
            $t->string('provider_dispute_id', 64)->nullable();
            $t->string('dispute_status', 24)->nullable();
            $t->json('payload')->nullable();
            $t->unsignedInteger('refunded_amount')->default(0);
            $t->enum('refund_status', ['none', 'partial', 'full', 'chargeback'])->default('none');
            $t->json('refund_payload')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->boolean('needs_review')->default(false);
            $t->string('source', 32)->nullable();
            $t->unsignedBigInteger('import_batch_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['provider', 'provider_payment_intent_id']);
        });

        Schema::connection('demos_db')->create('account_keys', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->enum('module', ['upwork', 'ppc'])->default('ppc');
            $t->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $t->string('brand_url')->nullable();
            $t->text('stripe_publishable_key')->nullable();
            $t->text('stripe_secret_key')->nullable();
            $t->string('stripe_webhook_secret')->nullable();
            $t->text('paypal_client_id')->nullable();
            $t->text('paypal_secret')->nullable();
            $t->string('paypal_webhook_id')->nullable();
            $t->string('paypal_base_url')->nullable();
            $t->enum('status', ['active', 'inactive'])->default('active');
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('profile_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->nullable()->index();
            $t->morphs('user');
            $t->string('profile')->nullable();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('alternate_email')->nullable();
            $t->string('phone')->nullable();
            $t->longText('address')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::connection('demos_db')->create('client_tickets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $t->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $t->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $t->string('subject');
            $t->longText('description');
            $t->string('attachment')->nullable();
            $t->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $t->enum('status', ['open', 'in_progress', 'on_hold', 'resolved', 'closed', 'reopened'])->default('open');
            $t->string('source')->default('crm');
            $t->boolean('is_client_visible')->default(true);
            $t->boolean('is_internal')->default(false);
            $t->timestamp('closed_at')->nullable();
            $t->unsignedBigInteger('closed_by')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::connection('demos_db')->create('projects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->string('title');
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('front_seller_id')->constrained('sellers');
            $t->foreignId('owner_seller_id')->constrained('sellers');
            $t->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $t->date('start_date')->nullable();
            $t->date('due_date')->nullable();
            $t->text('description')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('pm_assigned_at')->nullable();
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('project_tasks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->nullable()->index();
            $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $t->string('title');
            $t->longText('description')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('sellers')->nullOnDelete();
            $t->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $t->date('due_date')->nullable();
            $t->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('low');
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('order_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $t->string('sender_type', 16);
            $t->unsignedBigInteger('sender_id');
            $t->text('body');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('questionnairs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->nullable()->index();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->string('service_name')->nullable();
            $t->json('meta')->nullable();
            $t->char('brief_token', 36)->nullable();
            $t->timestamp('brief_token_expires_at')->nullable();
            $t->enum('status', ['pending', 'progress', 'completed'])->default('pending');
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('performance_bonuses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->nullable()->index();
            $t->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $t->decimal('target_revenue', 12, 2);
            $t->decimal('bonus_amount', 12, 2);
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->string('currency', 10)->default('USD');
            $t->string('status')->default('pending');
            $t->timestamps();
        });

        Schema::connection('demos_db')->create('risky_clients', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->nullable()->index();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->enum('risk_level', ['low', 'medium', 'high']);
            $t->decimal('risk_score', 5, 2)->nullable();
            $t->json('features')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('demos_db');
        $schema->dropIfExists('risky_clients');
        $schema->dropIfExists('performance_bonuses');
        $schema->dropIfExists('questionnairs');
        $schema->dropIfExists('order_messages');
        $schema->dropIfExists('project_tasks');
        $schema->dropIfExists('projects');
        $schema->dropIfExists('client_tickets');
        $schema->dropIfExists('profile_details');
        $schema->dropIfExists('account_keys');
        $schema->dropIfExists('payments');
        $schema->dropIfExists('payment_links');
        $schema->dropIfExists('orders');
        $schema->dropIfExists('lead_assignments');
        $schema->dropIfExists('leads');
        $schema->dropIfExists('sellers');
        $schema->dropIfExists('clients');
        $schema->dropIfExists('admins');
        $schema->dropIfExists('brands');
    }
};
