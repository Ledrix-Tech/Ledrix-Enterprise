<?php

namespace App\Support;

use App\Models\Central\PackagePricing;
use Illuminate\Support\Collection;

class MarketingTrialCopy
{
    public static function label(?PackagePricing $package): string
    {
        if (! $package) {
            return 'free trial';
        }

        $days = (int) $package->trial_days;

        return $days > 0 ? "{$days}-day free trial" : 'free trial';
    }

    public static function startCta(?PackagePricing $package): string
    {
        if (! $package) {
            return 'Start free trial';
        }

        $days = (int) $package->trial_days;

        return $days > 0 ? "Start {$days}-day free trial" : 'Start free trial';
    }

    /**
     * Generic trial copy when no specific plan is selected.
     * Avoids showing the longest trial (e.g. Premium) for all plans.
     */
    public static function genericLabel(Collection $packages): string
    {
        if ($packages->isEmpty()) {
            return 'free trial';
        }

        $days = $packages
            ->pluck('trial_days')
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d > 0)
            ->unique()
            ->sort()
            ->values();

        if ($days->isEmpty()) {
            return 'free trial';
        }

        if ($days->count() === 1) {
            return "{$days->first()}-day free trial";
        }

        return 'plan-based free trial';
    }

    /**
     * Human-readable trial range when plans differ, e.g. "7–21-day free trials".
     */
    public static function rangeLabel(Collection $packages): string
    {
        if ($packages->isEmpty()) {
            return 'free trial';
        }

        $days = $packages
            ->pluck('trial_days')
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d > 0)
            ->unique()
            ->sort()
            ->values();

        if ($days->isEmpty()) {
            return 'free trial';
        }

        if ($days->count() === 1) {
            return "{$days->first()}-day free trial";
        }

        return "{$days->first()}–{$days->last()}-day free trials";
    }

    public static function startCtaOnPlan(?PackagePricing $package): string
    {
        if (! $package) {
            return 'Start free trial';
        }

        $days = (int) $package->trial_days;
        $name = $package->name ?? 'plan';

        return $days > 0 ? "Start {$days}-day trial on {$name}" : "Get started on {$name}";
    }

    public static function startGenericCta(Collection $packages): string
    {
        $label = self::genericLabel($packages);

        return str_starts_with($label, 'plan-based')
            ? 'Start free trial'
            : 'Start ' . $label;
    }
}
