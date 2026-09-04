<?php

use App\Http\Controllers\Central\AuditLogController;
use App\Http\Controllers\Central\DemoRequestController;
use App\Http\Controllers\Central\InviteController;
use App\Http\Controllers\Central\StripeController;
use App\Http\Controllers\Central\MembershipController;
use App\Http\Controllers\Central\PlatformBillingSettingsController;
use App\Http\Controllers\Central\PlatformFxRateController;
use App\Http\Controllers\Central\PlatformSupportTicketController;
use App\Http\Controllers\Central\ReferralController;
use App\Http\Controllers\Central\RenewalRequestController;
use App\Http\Controllers\Central\SubscriptionPaymentController;
use App\Http\Controllers\Central\SuperDashboardController;
use App\Http\Controllers\Central\AuthorizeController;
use App\Http\Controllers\Central\SystemAnnouncementController;
use App\Http\Controllers\Central\PlatformStatusController;
use App\Http\Controllers\Central\SuperAdminTenantDomainController;
use App\Http\Controllers\Central\TeamController;
use App\Http\Controllers\Central\TenantDataErasureController;
use App\Http\Controllers\Central\TenantDataExportController;
use App\Http\Controllers\Central\TenantApiTokenController;
use App\Http\Controllers\Central\TenantFeatureController;
use App\Http\Controllers\Central\TwoFactorController;
use App\Http\Controllers\Central\WebhookEventController;
use App\Http\Controllers\Tenant\ContactQueryController;
use App\Http\Controllers\Central\ImpersonationController;
use App\Http\Controllers\Central\PlatformThemeController;
use App\Http\Controllers\Central\PlatformSsoSettingsController;
use App\Http\Controllers\Central\PricingController;
use App\Http\Controllers\Central\SsoController;
use Illuminate\Support\Facades\Route;

// super admin routes
Route::group(['prefix' => 'super-admin', 'namespace' => 'SuperAdmin'], function () {
    // admin auth
    Route::get('/login', [AuthorizeController::class, 'adminLoginPage'])->name('super-admin.login.get');
    Route::post('/login', [AuthorizeController::class, 'adminLoginPost'])->name('super-admin.login.post');
    Route::get('/forgot-password', [AuthorizeController::class, 'adminForgotPage'])->name('super-admin.forgot.get');
    Route::get('/reset/{token?}/password', [AuthorizeController::class, 'adminResetPage'])->name('super-admin.reset.get');
    Route::post('/forgot-password', [AuthorizeController::class, 'adminForgotPost'])->name('super-admin.forgot.post');
    Route::post('/reset-password', [AuthorizeController::class, 'adminResetPost'])->name('super-admin.reset.post');
    Route::get('/logout', [AuthorizeController::class, 'adminlogout'])->name('super-admin.logout');

    Route::get('/sso/redirect', [SsoController::class, 'redirect'])->name('super-admin.sso.redirect');
    Route::get('/sso/callback', [SsoController::class, 'callback'])->name('super-admin.sso.callback');

    Route::get('/invite/{token}', [InviteController::class, 'showAccept'])->name('super-admin.invite.accept');
    Route::post('/invite/{token}', [InviteController::class, 'acceptInvite'])->name('super-admin.invite.accept.post');

    Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('super-admin.2fa.challenge');
    Route::post('/2fa/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('super-admin.2fa.challenge.post');

    // Any active SA — read + support ops
    Route::middleware(['super-admin', '2fa.forced:super_admin'])->group(function () {
        Route::get('/dashboard', [SuperDashboardController::class, 'superDashboard'])->name('super-admin.index.get');

        Route::get('/2fa/setup', [TwoFactorController::class, 'showSetup'])->name('super-admin.2fa.setup');
        Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('super-admin.2fa.enable');
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('super-admin.2fa.disable');

        Route::get('/contact-queries', [ContactQueryController::class, 'superContactQuries'])->name('super-admin.contact-queries.get');
        Route::post('/contact-update-status', [ContactQueryController::class, 'updateContactStatus'])
            ->name('super-admin.contact-status.post');

        Route::get('/company-profile', [MembershipController::class, 'superCompanyProfiles'])->name('super-admin.company-profile.get');
        Route::get('/company/{id}', [MembershipController::class, 'superTenantShow'])->name('super-admin.tenant.show')->whereNumber('id');
        Route::get('/company/{tenantId}/invoices/{invoiceId}', [MembershipController::class, 'superTenantInvoiceShow'])
            ->name('super-admin.tenant.invoice.show')
            ->whereNumber(['tenantId', 'invoiceId']);
        Route::get('/company/{tenantId}/invoices/{invoiceId}/pdf', [MembershipController::class, 'superTenantInvoicePdf'])
            ->name('super-admin.tenant.invoice.pdf')
            ->whereNumber(['tenantId', 'invoiceId']);
        Route::get('/company/{tenantId}/features', [TenantFeatureController::class, 'edit'])->name('super-admin.tenant.features.get')->whereNumber('tenantId');

        Route::get('/subscription-payments', [SubscriptionPaymentController::class, 'pending'])->name('super-admin.subscription-payments.get');
        Route::get('/renewal-requests', [RenewalRequestController::class, 'index'])->name('super-admin.renewal-requests.get');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('super-admin.audit-logs.get');
        Route::get('/announcements', [SystemAnnouncementController::class, 'index'])->name('super-admin.announcements.get');
        Route::get('/status', [PlatformStatusController::class, 'index'])->name('super-admin.status.get');

        Route::get('/support-tickets', [PlatformSupportTicketController::class, 'index'])->name('super-admin.support-tickets.get');
        Route::get('/support-tickets/{id}', [PlatformSupportTicketController::class, 'show'])->name('super-admin.support-tickets.show')->whereNumber('id');
        Route::post('/support-tickets/{id}/reply', [PlatformSupportTicketController::class, 'reply'])->name('super-admin.support-tickets.reply')->whereNumber('id');
        Route::post('/support-tickets/{id}/status', [PlatformSupportTicketController::class, 'updateStatus'])->name('super-admin.support-tickets.status')->whereNumber('id');

        Route::get('/data-exports', [TenantDataExportController::class, 'index'])->name('super-admin.data-exports.get');
        Route::get('/data-exports/{id}/download', [TenantDataExportController::class, 'download'])
            ->name('super-admin.data-exports.download')
            ->whereNumber('id');

        Route::get('/demo-requests', [DemoRequestController::class, 'index'])->name('super-admin.demo-requests.get');
        Route::put('/demo-requests/{id}', [DemoRequestController::class, 'update'])->name('super-admin.demo-requests.update')->whereNumber('id');

        Route::get('/referrals', [ReferralController::class, 'index'])->name('super-admin.referrals.get');
        Route::get('/webhook-events', [WebhookEventController::class, 'index'])->name('super-admin.webhook-events.get');
        Route::get('/webhook-events/{id}', [WebhookEventController::class, 'show'])->name('super-admin.webhook-events.show')->whereNumber('id');
    });

    // Owner or admin — platform mutations (middleware also authenticates)
    Route::middleware(['super-admin:admin', '2fa.forced:super_admin'])->group(function () {
        Route::get('/pricing-packages', [PricingController::class, 'superPackegsPricing'])->name('super-admin.pricing-packages.get');
        Route::post('/pricing-packages', [PricingController::class, 'pricingPackageStore'])->name('super-admin.pricing-packages.post');
        Route::put('/pricing-package/{id?}/update', [PricingController::class, 'pricingPackageUpdate'])->name('super-admin.pricing-packages.update');
        Route::delete('/pricing-package/{id}', [PricingController::class, 'destroy'])->name('super-admin.pricing-packages.destroy')->whereNumber('id');

        Route::post('/company-status', [MembershipController::class, 'superTenantStatus'])->name('super-company.company-status');
        Route::post('/company/{tenantId}/invoices/{invoiceId}/void', [MembershipController::class, 'superTenantInvoiceVoid'])
            ->name('super-admin.tenant.invoice.void')
            ->whereNumber(['tenantId', 'invoiceId']);
        Route::post('/company/{tenantId}/invoices/{invoiceId}/refund', [MembershipController::class, 'superTenantInvoiceRefund'])
            ->name('super-admin.tenant.invoice.refund')
            ->whereNumber(['tenantId', 'invoiceId']);
        Route::put('/company/{id}/domain', [SuperAdminTenantDomainController::class, 'update'])
            ->name('super-admin.tenant.domain.update')
            ->whereNumber('id');
        Route::post('/company/{id}/domain/verify', [SuperAdminTenantDomainController::class, 'verify'])
            ->name('super-admin.tenant.domain.verify')
            ->whereNumber('id');
        Route::post('/company/{id}/domain/unverify', [SuperAdminTenantDomainController::class, 'unverify'])
            ->name('super-admin.tenant.domain.unverify')
            ->whereNumber('id');
        Route::post('/company/{id}/suspend', [MembershipController::class, 'suspend'])
            ->name('super-admin.tenant.suspend')
            ->whereNumber('id');
        Route::post('/company/{id}/activate', [MembershipController::class, 'activate'])
            ->name('super-admin.tenant.activate')
            ->whereNumber('id');
        Route::post('/company/{id}/offboard', [MembershipController::class, 'offboard'])
            ->name('super-admin.tenant.offboard')
            ->whereNumber('id');
        Route::post('/company/{id}/restore', [MembershipController::class, 'restoreOffboarded'])
            ->name('super-admin.tenant.restore')
            ->whereNumber('id');
        Route::post('/company/{id}/impersonate', [ImpersonationController::class, 'start'])
            ->name('super-admin.tenant.impersonate')
            ->whereNumber('id');
        Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])
            ->name('super-admin.impersonation.stop');
        Route::post('/company/{tenantId}/data-export', [TenantDataExportController::class, 'generateNow'])
            ->name('super-admin.tenant.data-export.generate')
            ->whereNumber('tenantId');
        Route::post('/company/{tenantId}/data-erasure', [TenantDataErasureController::class, 'eraseNow'])
            ->name('super-admin.tenant.data-erasure.erase')
            ->whereNumber('tenantId');
        Route::post('/company/{tenantId}/backup', [TenantDataExportController::class, 'createBackup'])
            ->name('super-admin.tenant.backup')
            ->whereNumber('tenantId');
        Route::post('/data-exports/{id}/approve', [TenantDataExportController::class, 'approve'])
            ->name('super-admin.data-exports.approve')
            ->whereNumber('id');
        Route::post('/data-exports/{id}/reject', [TenantDataExportController::class, 'reject'])
            ->name('super-admin.data-exports.reject')
            ->whereNumber('id');
        Route::post('/data-exports/{id}/restore-dry-run', [TenantDataExportController::class, 'restoreDryRun'])
            ->name('super-admin.data-exports.restore-dry-run')
            ->whereNumber('id');
        Route::post('/data-exports/{id}/restore', [TenantDataExportController::class, 'restoreForce'])
            ->name('super-admin.data-exports.restore')
            ->whereNumber('id');
        Route::put('/company/{tenantId}/features', [TenantFeatureController::class, 'update'])->name('super-admin.tenant.features.update')->whereNumber('tenantId');
        Route::delete('/company/{tenantId}/features', [TenantFeatureController::class, 'reset'])->name('super-admin.tenant.features.reset')->whereNumber('tenantId');

        Route::post('/subscription-payments/{paymentId}/confirm', [SubscriptionPaymentController::class, 'confirm'])->name('super-admin.subscription-payments.confirm');

        Route::get('/billing-settings', [PlatformBillingSettingsController::class, 'edit'])->name('super-admin.billing-settings.get');
        Route::put('/billing-settings/{provider}', [PlatformBillingSettingsController::class, 'update'])
            ->name('super-admin.billing-settings.update')
            ->where('provider', 'stripe|meezan');

        Route::get('/fx-rates', [PlatformFxRateController::class, 'edit'])->name('super-admin.fx-rates.get');
        Route::post('/fx-rates', [PlatformFxRateController::class, 'store'])->name('super-admin.fx-rates.store');
        Route::delete('/fx-rates', [PlatformFxRateController::class, 'destroy'])->name('super-admin.fx-rates.destroy');

        Route::get('/theme', [PlatformThemeController::class, 'edit'])->name('super-admin.theme.get');
        Route::put('/theme', [PlatformThemeController::class, 'update'])->name('super-admin.theme.update');
        Route::post('/theme/reset', [PlatformThemeController::class, 'reset'])->name('super-admin.theme.reset');

        Route::get('/sso-settings', [PlatformSsoSettingsController::class, 'edit'])->name('super-admin.sso-settings.get');
        Route::put('/sso-settings', [PlatformSsoSettingsController::class, 'update'])->name('super-admin.sso-settings.update');

        Route::post('/webhook-events/{id}/retry', [WebhookEventController::class, 'retry'])
            ->name('super-admin.webhook-events.retry')
            ->whereNumber('id');

        Route::post('/renewal-requests/{id}/cancel', [RenewalRequestController::class, 'cancel'])
            ->name('super-admin.renewal-requests.cancel')
            ->whereNumber('id');

        Route::post('/audit-logs/clear', [AuditLogController::class, 'clear'])->name('super-admin.audit-logs.clear');

        Route::post('/announcements', [SystemAnnouncementController::class, 'store'])->name('super-admin.announcements.store');
        Route::put('/announcements/{id}', [SystemAnnouncementController::class, 'update'])->name('super-admin.announcements.update')->whereNumber('id');
        Route::delete('/announcements/{id}', [SystemAnnouncementController::class, 'destroy'])->name('super-admin.announcements.destroy')->whereNumber('id');

        Route::put('/status/components/{id}', [PlatformStatusController::class, 'updateComponent'])
            ->name('super-admin.status.components.update')
            ->whereNumber('id');
        Route::post('/status/incidents', [PlatformStatusController::class, 'storeIncident'])
            ->name('super-admin.status.incidents.store');
        Route::put('/status/incidents/{id}', [PlatformStatusController::class, 'updateIncident'])
            ->name('super-admin.status.incidents.update')
            ->whereNumber('id');
        Route::delete('/status/incidents/{id}', [PlatformStatusController::class, 'destroyIncident'])
            ->name('super-admin.status.incidents.destroy')
            ->whereNumber('id');

        Route::post('/referrals', [ReferralController::class, 'issue'])->name('super-admin.referrals.issue');
        Route::post('/referrals/{id}/reward', [ReferralController::class, 'reward'])->name('super-admin.referrals.reward')->whereNumber('id');
        Route::post('/referrals/{id}/expire', [ReferralController::class, 'expire'])->name('super-admin.referrals.expire')->whereNumber('id');

        Route::post('/company/{tenantId}/api-tokens', [TenantApiTokenController::class, 'store'])
            ->name('super-admin.tenant.api-tokens.store')
            ->whereNumber('tenantId');
        Route::post('/company/{tenantId}/api-tokens/{tokenId}/revoke', [TenantApiTokenController::class, 'revoke'])
            ->name('super-admin.tenant.api-tokens.revoke')
            ->whereNumber(['tenantId', 'tokenId']);

        Route::post('/company/{tenant}/send-renewal-approval', [StripeController::class, 'sendRenewalApproval'])
            ->name('super-renew.send')
            ->whereNumber('tenant');
    });

    // Owner only — team management
    Route::middleware(['super-admin:owner', '2fa.forced:super_admin'])->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('super-admin.team.get');
        Route::put('/team/{id}/status', [TeamController::class, 'updateStatus'])->name('super-admin.team.status')->whereNumber('id');
        Route::put('/team/{id}/role', [TeamController::class, 'updateRole'])->name('super-admin.team.role')->whereNumber('id');
        Route::post('/team/invite', [InviteController::class, 'sendInvite'])->name('super-admin.invite.send');
        Route::delete('/team/invites/{id}', [InviteController::class, 'revoke'])->name('super-admin.invite.revoke')->whereNumber('id');
    });
});
