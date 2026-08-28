<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ManagementController;
use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Seller\PagesController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\API\Client\BriefController as ClientBriefController;
use App\Http\Controllers\Seller\SellerAuthController;
use App\Http\Controllers\Seller\SellerDataController;
use App\Http\Controllers\Seller\SellerBrandController;
use App\Http\Controllers\Seller\SellerLeadsController;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerExportController;
use App\Http\Controllers\Seller\SellerTwoFactorController;
use App\Http\Controllers\Seller\SellerMessageController;

Route::group(['prefix' => 'seller'], function () {
    Route::get('/login', [SellerAuthController::class, 'sellerLoginPage'])->name('seller.login.get');
    Route::post('/login', [SellerAuthController::class, 'sellerLoginPost'])->name('seller.login.post');
    Route::get('/forgot-password', [SellerAuthController::class, 'sellerForgotPage'])->name('seller.forgot.get');
    Route::get('/reset/{token?}/password', [SellerAuthController::class, 'sellerResetPage'])->name('seller.reset.get');
    Route::post('/forgot-password', [SellerAuthController::class, 'sellerForgotPost'])->name('seller.forgot.post');
    Route::post('/reset-password', [SellerAuthController::class, 'sellerResetPost'])->name('seller.reset.post');
    Route::post('/logout', [SellerAuthController::class, 'sellerlogout'])->name('seller.logout');

    Route::get('/2fa/challenge', [SellerTwoFactorController::class, 'challenge'])->name('seller.2fa.challenge');
    Route::post('/2fa/challenge', [SellerTwoFactorController::class, 'verifyChallenge'])->name('seller.2fa.challenge.post');

    Route::group(['middleware' => ['seller', 'crm.workspace', 'tenant.maintenance', 'tenant.feature:ppc_module']], function () {
        Route::get('/2fa', [SellerTwoFactorController::class, 'showSetup'])->name('seller.2fa.setup');
        Route::post('/2fa/enable', [SellerTwoFactorController::class, 'enable'])->name('seller.2fa.enable');
        Route::post('/2fa/disable', [SellerTwoFactorController::class, 'disable'])->name('seller.2fa.disable');

        Route::post('/lead/{lead}/finish', [OrderController::class, 'sellerLeadFinish'])->name('seller.lead.finish');
        Route::post('/lead/update-status', [SellerLeadsController::class, 'updateAssignedLeadStatus'])->name('seller.assignment.update-status');
        Route::post('/lead-assign', [AdminSellerController::class, 'assignLeadSeller'])->name('seller.lead-assign.post');

        Route::get('/dashboard', [PagesController::class, 'sellerDashboard'])->name('seller.index.get');
        Route::get('/messages', [SellerMessageController::class, 'index'])->name('seller.messages.get');
        Route::post('/messages/{order}', [SellerMessageController::class, 'store'])
            ->name('seller.messages.store')
            ->whereNumber('order');

        Route::get('/clients', [PagesController::class, 'sellerClients'])->name('seller.clients.get');
        Route::get('/briefs', [ClientBriefController::class, 'sellerBriefsHub'])->name('seller.briefs.get');
        Route::get('/client/{id?}/briefs', [ManagementController::class, 'clientBriefs'])->name('seller.client-briefs.get');
        Route::post('/brief-status', [ClientBriefController::class, 'sellerBriefStatusPost'])->name('seller.brief-status.post');
        Route::post('/client-delete', [ManagementController::class, 'deleteClient'])->name('seller.client.delete');
        Route::post('/client-status', [ManagementController::class, 'updateClientStatus'])->name('seller.client.updateStatus');

        Route::get('/brands', [SellerBrandController::class, 'sellerBrands'])->name('seller.brands.get');
        Route::post('/brand-post', [SellerBrandController::class, 'sellerBrandPost'])->name('seller.brand.post');
        Route::get('/brand-payments', [SellerBrandController::class, 'sellerBrandPayments'])->name('seller.brand-payments.get');
        Route::get('/brand-payouts', [SellerBrandController::class, 'sellerBrandPayouts'])->name('seller.brand-payouts.get');

        Route::middleware('tenant.feature:api_access')->group(function () {
            Route::get('/domain-scripts', [PagesController::class, 'sellerDomainScripts'])->name('seller.domain-script.get');
        });

        Route::get('/sellers', [SellerDataController::class, 'sellerSellers'])->name('seller.sellers.get');
        Route::post('/seller-post', [SellerDataController::class, 'sellerSellerPost'])->name('seller.seller.post');
        Route::get('/performance/{seller?}', [SellerDataController::class, 'sellerSellerPerformance'])
            ->whereNumber('seller')
            ->name('seller.seller-performance.get');
        Route::get('/seller/{seller}/performance', [SellerDataController::class, 'sellerSellerPerformanceLegacy'])
            ->whereNumber('seller')
            ->name('seller.seller-performance.legacy');

        Route::middleware('tenant.feature:seller_leaderboard')->group(function () {
            Route::get('/seller-leaderboard', [SellerDataController::class, 'sellerSellerLeaderboard'])->name('seller.seller-leaderboard.get');
        });

        Route::post('/seller/change-domain', [SellerDataController::class, 'changeDomain'])->name('seller.seller.changeDomain');
        Route::post('/seller-status', [SellerDataController::class, 'sellerUpdateStatus'])->name('seller.seller.updateStatus');

        Route::get('/leads', [SellerLeadsController::class, 'sellerLeads'])->name('seller.leads.get');
        Route::get('/lead/{id?}/details', [SellerLeadsController::class, 'sellerLeadDetails'])->name('seller.lead-details.get');
        Route::get('/assigned-leads', [SellerLeadsController::class, 'sellerAssignedLeads'])->name('seller.assigned-leads.get');
        Route::post('/lead-delete/{id?}', [ManagementController::class, 'deleteLeads'])->name('seller.leads.delete');

        Route::get('/orders', [SellerOrderController::class, 'sellerOrders'])->name('seller.orders.get');
        Route::get('/renewed/{order}/orders', [SellerOrderController::class, 'sellerOrderRenewals'])->name('seller.renewed-orders.get');
        Route::get('/assigned-leads-orders', [SellerOrderController::class, 'sellerPMOrders'])->name('seller.assigned-leads-orders.get');

        Route::middleware('tenant.feature:projects')->group(function () {
            Route::get('/projects', [\App\Http\Controllers\Admin\ProjectController::class, 'index'])->name('seller.projects.index');
            Route::post('/projects', [\App\Http\Controllers\Admin\ProjectController::class, 'store'])->name('seller.projects.store');
            Route::get('/projects/{id}', [\App\Http\Controllers\Admin\ProjectController::class, 'show'])->name('seller.projects.show')->whereNumber('id');
            Route::put('/projects/{id}', [\App\Http\Controllers\Admin\ProjectController::class, 'update'])->name('seller.projects.update')->whereNumber('id');
            Route::delete('/projects/{id}', [\App\Http\Controllers\Admin\ProjectController::class, 'destroy'])->name('seller.projects.destroy')->whereNumber('id');
            Route::post('/projects/{id}/tasks', [\App\Http\Controllers\Admin\ProjectController::class, 'storeTask'])->name('seller.projects.tasks.store')->whereNumber('id');
            Route::put('/projects/{id}/tasks/{task}', [\App\Http\Controllers\Admin\ProjectController::class, 'updateTask'])->name('seller.projects.tasks.update')->whereNumber('id')->whereNumber('task');
            Route::delete('/projects/{id}/tasks/{task}', [\App\Http\Controllers\Admin\ProjectController::class, 'destroyTask'])->name('seller.projects.tasks.destroy')->whereNumber('id')->whereNumber('task');
        });

        Route::middleware('tenant.feature:stripe|paypal')->group(function () {
            Route::get('/payments', [SellerOrderController::class, 'sellerPayments'])->name('seller.payments.get');
        });

        Route::middleware('tenant.feature:support_tickets')->group(function () {
            Route::get('/order/{order}/tickets', [SellerExportController::class, 'sellerOrderTickets'])->name('seller.order-tickets.get');
            Route::get('/order/ticket/{id}/details', [SellerExportController::class, 'getTicketDetails'])->name('seller.tickets.details');
            Route::post('/ticket-delete/{id?}', [SellerExportController::class, 'deleteTickets'])->name('seller.tickets.delete');
        });

        Route::middleware('tenant.feature:dual_invoicing')->group(function () {
            Route::get('/generate/{order}/invoice', [ManagementController::class, 'generateInvoice'])
                ->whereNumber('order')
                ->name('seller.order.generate-invoice');
        });

        Route::post('/domain-delete', [ManagementController::class, 'deleteDomain'])->name('seller.domain.delete');
        Route::post('/domain-status', [ManagementController::class, 'updateDomainStatus'])->name('seller.domain.updateStatus');
    });
});
