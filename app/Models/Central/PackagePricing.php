<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagePricing extends Model
{
    protected $connection = 'central';
    protected $table      = 'package_pricings';

    protected $fillable = [
        'name', 'slug', 'description',
        'stripe_monthly_price_id', 'stripe_yearly_price_id',
        'monthly_price', 'yearly_price', 'monthly_price_pkr', 'yearly_price_pkr', 'currency', 'trial_days',

        // Limits
        'max_brands', 'max_sellers', 'max_admins', 'max_clients',
        'max_leads_per_month', 'max_orders', 'max_payment_links',
        'max_account_keys', 'max_projects', 'max_storage_mb',
        'import_max_rows_per_upload', 'import_max_uploads_per_month',
        'import_multi_brand_allowed', 'import_reimport_allowed',

        // Features
        'feature_ppc_module', 'feature_upwork_module',
        'feature_milestone_payments', 'feature_stripe',
        'feature_paypal', 'feature_webhooks',
        'feature_chargeback_tracking', 'feature_dual_invoicing',
        'feature_client_portal', 'feature_lead_prediction',
        'feature_seller_leaderboard', 'feature_performance_bonus',
        'feature_projects', 'feature_support_tickets',
        'feature_api_access', 'feature_custom_domain',
        'feature_white_label',

        // Display
        'is_popular', 'is_public', 'sort_order',
        'badge_text', 'features_html', 'status',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price'  => 'decimal:2',
        'monthly_price_pkr' => 'decimal:2',
        'yearly_price_pkr'  => 'decimal:2',
        'is_popular'    => 'boolean',
        'is_public'     => 'boolean',
        'import_multi_brand_allowed' => 'boolean',
        'import_reimport_allowed'    => 'boolean',

        // Every feature cast to boolean for direct use
        'feature_ppc_module'          => 'boolean',
        'feature_upwork_module'       => 'boolean',
        'feature_milestone_payments'  => 'boolean',
        'feature_stripe'              => 'boolean',
        'feature_paypal'              => 'boolean',
        'feature_webhooks'            => 'boolean',
        'feature_chargeback_tracking' => 'boolean',
        'feature_dual_invoicing'      => 'boolean',
        'feature_client_portal'       => 'boolean',
        'feature_lead_prediction'     => 'boolean',
        'feature_seller_leaderboard'  => 'boolean',
        'feature_performance_bonus'   => 'boolean',
        'feature_projects'            => 'boolean',
        'feature_support_tickets'     => 'boolean',
        'feature_api_access'          => 'boolean',
        'feature_custom_domain'       => 'boolean',
        'feature_white_label'         => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function renewalRequests(): HasMany
    {
        return $this->hasMany(TenantRenewalRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    // ── Feature Helpers ────────────────────────────────────

    // Direct column access — no JSON parsing needed
    // Usage: $plan->hasFeature('webhooks')
    public function hasFeature(string $feature): bool
    {
        $key = 'feature_' . $feature;
        return (bool) ($this->$key ?? false);
    }

    // ── Limit Helpers ──────────────────────────────────────

    // -1 means unlimited
    // Usage: $plan->isUnlimited('max_brands')
    public function isUnlimited(string $limitKey): bool
    {
        return (int) ($this->$limitKey ?? 0) === -1;
    }

    // Usage: $plan->getLimit('max_brands')
    public function getLimit(string $limitKey): int
    {
        return (int) ($this->$limitKey ?? 0);
    }

    /** Pricing comparison cell: numbers, not a checkmark. */
    public function sheetImportComparisonLabel(): string
    {
        $rows = $this->import_max_rows_per_upload;
        $uploads = $this->import_max_uploads_per_month;

        if ($rows === null && $uploads === null) {
            $slug = strtolower((string) $this->slug);
            if (str_contains($slug, 'premium') || str_contains($slug, 'agency') || str_contains($slug, 'enterprise')) {
                return 'Unlimited, multi-brand';
            }
            if (str_contains($slug, 'standard') || str_contains($slug, 'growth')) {
                return '1,000 rows, 5/mo, multi-brand';
            }

            return '150 rows, 1/mo, single brand';
        }

        $multi = (bool) $this->import_multi_brand_allowed;
        $brand = $multi ? 'multi-brand' : 'single brand';

        if ((int) $rows === -1 && (int) $uploads === -1) {
            return 'Unlimited, '.$brand;
        }

        $rowText = (int) $rows === -1 ? 'Unlimited rows' : number_format((int) $rows).' rows';
        $uploadText = (int) $uploads === -1 ? 'unlimited/mo' : (int) $uploads.'/mo';

        return $rowText.', '.$uploadText.', '.$brand;
    }

    // ── Price Helpers ──────────────────────────────────────

    public function isFree(): bool
    {
        return $this->monthly_price == 0
            && $this->yearly_price == 0;
    }

    public function priceFor(string $cycle): float
    {
        return $cycle === 'yearly'
            ? (float) $this->yearly_price
            : (float) $this->monthly_price;
    }

    public function stripePrice(string $cycle): ?string
    {
        return $cycle === 'yearly'
            ? $this->stripe_yearly_price_id
            : $this->stripe_monthly_price_id;
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)
                     ->orderBy('sort_order');
    }
}