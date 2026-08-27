<?php

namespace App\View\Composers;

use App\Support\MarketingTrialCopy;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MarketingTrialComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();
        $packages = $data['packages'] ?? collect();
        if (! $packages instanceof Collection) {
            $packages = collect($packages);
        }

        $popularPackage = $data['popularPackage']
            ?? ($packages->firstWhere('is_popular', true) ?? $packages->first());

        $featuredPackage = $data['featuredPackage']
            ?? $data['featured']
            ?? $popularPackage;

        $view->with([
            'popularPackage'         => $popularPackage,
            'featuredPackage'        => $featuredPackage,
            'trialLabelGeneric'      => MarketingTrialCopy::genericLabel($packages),
            'trialRangeLabel'        => MarketingTrialCopy::rangeLabel($packages),
            'trialLabelPopular'      => MarketingTrialCopy::label($popularPackage),
            'trialStartCtaPopular'   => MarketingTrialCopy::startCta($popularPackage),
            'trialStartCtaGeneric'   => MarketingTrialCopy::startGenericCta($packages),
            'trialStartCtaOnPopular' => MarketingTrialCopy::startCtaOnPlan($popularPackage),
            'trialLabelFeatured'     => MarketingTrialCopy::label($featuredPackage),
            'trialStartCtaOnFeatured'=> MarketingTrialCopy::startCtaOnPlan($featuredPackage),
        ]);
    }
}
