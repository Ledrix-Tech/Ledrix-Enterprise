<?php

use App\Http\Controllers\API\BrandingController;
use App\Http\Controllers\API\Client\BrandConfigController;
use App\Http\Controllers\API\Client\LeadsController as ClientLeadsController;
use App\Http\Controllers\Central\LeadsController as PlatformLeadsController;
use App\Http\Controllers\Central\PlatformStripeWebhookController;
use App\Http\Controllers\Central\ScimController;
use App\Http\Controllers\Compliances\RefundWebhookController;
use App\Http\Controllers\Seller\WebhookController;
use App\Http\Controllers\Upwork\DisputeController;
use App\Http\Controllers\Upwork\WebhookController as UpworkWebhookController;
use Illuminate\Support\Facades\Route;


Route::post('/crm-lead-post', [BrandingController::class, 'storeLead'])
    ->name('crm.leads.post');

Route::post('/webhooks/platform/stripe', PlatformStripeWebhookController::class)
    ->name('platform.stripe.webhook');

// SCIM 2.0 user provisioning (enterprise IdP sync)
Route::middleware(['scim.auth'])->prefix('scim/v2')->group(function () {
    Route::get('/ServiceProviderConfig', [ScimController::class, 'serviceProviderConfig']);
    Route::get('/Users', [ScimController::class, 'listUsers']);
    Route::post('/Users', [ScimController::class, 'createUser']);
    Route::get('/Users/{id}', [ScimController::class, 'showUser']);
    Route::patch('/Users/{id}', [ScimController::class, 'patchUser']);
    Route::delete('/Users/{id}', [ScimController::class, 'deleteUser']);
});

Route::middleware(['throttle:60,1', 'tenant.api'])->prefix('v1')->group(function () {
    Route::get('/company/check', [PlatformLeadsController::class, 'checkCompanyData'])
        ->name('api.v1.company.check');
    Route::post('/leads/classify', [PlatformLeadsController::class, 'classifyLead'])
        ->middleware('tenant.api:leads:classify')
        ->name('api.v1.leads.classify');

    // F-24 tenant management API
    Route::get('/company', [\App\Http\Controllers\API\Tenant\TenantManagementApiController::class, 'company'])
        ->middleware('tenant.api:company:read')
        ->name('api.v1.company.show');
    Route::get('/membership', [\App\Http\Controllers\API\Tenant\TenantManagementApiController::class, 'membership'])
        ->middleware('tenant.api:membership:read')
        ->name('api.v1.membership.show');
    Route::get('/invoices', [\App\Http\Controllers\API\Tenant\TenantManagementApiController::class, 'invoices'])
        ->middleware('tenant.api:invoices:read')
        ->name('api.v1.invoices.index');
    Route::get('/usage', [\App\Http\Controllers\API\Tenant\TenantManagementApiController::class, 'usage'])
        ->middleware('tenant.api:usage:read')
        ->name('api.v1.usage.show');
});

Route::post('/crm-order-post', [BrandingController::class, 'directOrder'])
    ->name('crm.order.post');

Route::get('/brand-config', [BrandConfigController::class, 'show']);

Route::get('/lead-script/{host?}.js', [BrandConfigController::class, 'showScript'])
    ->where('host', '.*')
    ->name('lead.script');

Route::post('/lead-post', [ClientLeadsController::class, 'storeLead'])->name('lead.post')->middleware('throttle:60,1');
Route::post('/post-lead', [ClientLeadsController::class, 'storeLead'])->name('post.lead')->middleware('throttle:60,1');

Route::prefix('/webhooks')->group(function () {
    Route::post('/stripe',  [WebhookController::class, 'handleWebhook'])->defaults('provider', 'stripe')->name('stripe.webhook');
    Route::post('/paypal',  [WebhookController::class, 'handleWebhook'])->defaults('provider', 'paypal')->name('paypal.webhook');

    Route::post('/stripe/refund', [RefundWebhookController::class, 'stripeRefundHandle'])
        ->name('stripe.refund.webhook');
    Route::post('/stripe/dispute', [RefundWebhookController::class, 'stripeDisputeHandle'])
        ->name('stripe.dispute.webhook');

    Route::post('/paypal/refund', [RefundWebhookController::class, 'paypalRefundHandle'])
        ->name('paypal.refund.webhook');
    Route::post('/paypal/dispute', [RefundWebhookController::class, 'paypalDisputeHandle'])
        ->name('paypal.dispute.webhook');

    Route::post('/upwork-stripe/refund', [DisputeController::class, 'stripeRefundHandle'])
        ->name('upwork-stripe.refund.webhook');
    Route::post('/upwork-stripe/dispute', [DisputeController::class, 'stripeDisputeHandle'])
        ->name('upwork-stripe.dispute.webhook');

    Route::post('/upwork/stripe', [UpworkWebhookController::class, 'handleWebhook'])
        ->defaults('provider', 'stripe')
        ->name('upwork.stripe.webhook');
    Route::post('/upwork/paypal', [UpworkWebhookController::class, 'handleWebhook'])
        ->defaults('provider', 'paypal')
        ->name('upwork.paypal.webhook');
});
