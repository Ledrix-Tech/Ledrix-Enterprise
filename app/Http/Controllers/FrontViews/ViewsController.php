<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;

class ViewsController extends Controller
{
    public function showHomePage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('front.pages.index', compact('packages'));
    }

    public function showContactPage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('front.pages.contact', compact('packages'));
    }

    public function showPricingPage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        $limitRows = [
            'max_brands'          => 'Brands',
            'max_sellers'         => 'Sellers',
            'max_admins'          => 'Admins',
            'max_clients'         => 'Clients',
            'max_leads_per_month' => 'Leads / month',
            'max_orders'          => 'Orders',
            'max_payment_links'   => 'Payment links',
            'max_projects'        => 'Projects',
            'max_storage_mb'      => 'Storage (MB)',
        ];

        $featureRows = [
            'feature_ppc_module'          => 'PPC module',
            'feature_milestone_payments'  => 'Milestone payments',
            'feature_stripe'              => 'Stripe payments',
            'feature_paypal'              => 'PayPal payments',
            'feature_webhooks'            => 'Webhooks',
            'feature_chargeback_tracking' => 'Chargeback tracking',
            'feature_dual_invoicing'      => 'Dual invoicing',
            'feature_client_portal'       => 'Client portal',
            'feature_lead_prediction'   => 'Lead prediction',
            'feature_seller_leaderboard'  => 'Seller leaderboard',
            'feature_performance_bonus'   => 'Performance bonus',
            'feature_projects'            => 'Projects module',
            'feature_support_tickets'     => 'Support tickets',
            'feature_api_access'          => 'API access',
            'feature_custom_domain'       => 'Custom domain',
            'feature_white_label'         => 'White label',
        ];

        return view('front.pages.pricing', compact('packages', 'limitRows', 'featureRows'));
    }

    public function showFeaturesPage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('front.pages.features', compact('packages'));
    }

    public function showAboutPage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('front.pages.about', compact('packages'));
    }

    public function showFaqPage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('front.pages.faq', compact('packages'));
    }

    public function showTermsPage()
    {
        return view('front.pages.terms');
    }

    public function showPrivacyPage()
    {
        return view('front.pages.privacy-policy');
    }

    public function showSecurityPage()
    {
        return view('front.pages.security');
    }
}
