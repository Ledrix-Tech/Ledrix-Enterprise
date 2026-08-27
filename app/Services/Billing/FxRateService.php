<?php

namespace App\Services\Billing;

use App\Models\Central\PlatformFxRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FxRateService
{
    public function tableReady(): bool
    {
        return Schema::connection('central')->hasTable('platform_fx_rates');
    }

    public function baseCurrency(): string
    {
        return strtoupper((string) config('billing.base_currency', 'USD'));
    }

    /**
     * How many units of $to equal 1 unit of $from.
     */
    public function rate(string $from, string $to, ?Carbon $asOf = null): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === '' || $to === '') {
            throw new RuntimeException('Currency codes are required for FX conversion.');
        }

        if ($from === $to) {
            return 1.0;
        }

        if ($direct = $this->lookupRate($from, $to, $asOf)) {
            return $direct;
        }

        if ($inverse = $this->lookupRate($to, $from, $asOf)) {
            return $inverse > 0 ? (1 / $inverse) : 0.0;
        }

        $base = $this->baseCurrency();
        if ($from !== $base && $to !== $base) {
            $toBase = $this->rate($from, $base, $asOf);
            $fromBase = $this->rate($base, $to, $asOf);

            return $toBase * $fromBase;
        }

        return $this->fallbackRate($from, $to);
    }

    public function convert(float $amount, string $from, string $to, ?Carbon $asOf = null): float
    {
        $converted = $amount * $this->rate($from, $to, $asOf);

        return BillingMoney::round($converted, $to);
    }

    /**
     * @return list<PlatformFxRate>
     */
    public function allRates(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        return PlatformFxRate::query()
            ->orderBy('base_currency')
            ->orderBy('quote_currency')
            ->get()
            ->all();
    }

    public function upsert(
        string $base,
        string $quote,
        float $rate,
        ?int $updatedBy = null,
        string $source = 'manual',
    ): PlatformFxRate {
        if (! $this->tableReady()) {
            throw new RuntimeException('Run central migrations for platform_fx_rates first.');
        }

        $base = strtoupper(trim($base));
        $quote = strtoupper(trim($quote));

        if ($base === $quote) {
            throw new RuntimeException('Base and quote currencies must differ.');
        }

        if ($rate <= 0) {
            throw new RuntimeException('FX rate must be greater than zero.');
        }

        return PlatformFxRate::query()->updateOrCreate(
            [
                'base_currency'  => $base,
                'quote_currency' => $quote,
            ],
            [
                'rate'         => $rate,
                'effective_at' => now(),
                'source'       => $source,
                'updated_by'   => $updatedBy,
            ]
        );
    }

    public function deletePair(string $base, string $quote): void
    {
        if (! $this->tableReady()) {
            return;
        }

        PlatformFxRate::query()
            ->where('base_currency', strtoupper($base))
            ->where('quote_currency', strtoupper($quote))
            ->delete();
    }

    /** Seed defaults from config when the table is empty. */
    public function seedDefaultsIfEmpty(?int $updatedBy = null): void
    {
        if (! $this->tableReady()) {
            return;
        }

        if (PlatformFxRate::query()->exists()) {
            return;
        }

        $base = $this->baseCurrency();
        $defaults = (array) config('billing.default_rates_from_usd', []);

        foreach ($defaults as $quote => $rate) {
            $quote = strtoupper((string) $quote);
            if ($quote === $base || (float) $rate <= 0) {
                continue;
            }
            $this->upsert($base, $quote, (float) $rate, $updatedBy, 'seed');
        }
    }

    private function lookupRate(string $from, string $to, ?Carbon $asOf): ?float
    {
        if (! $this->tableReady()) {
            return null;
        }

        $query = PlatformFxRate::query()
            ->where('base_currency', $from)
            ->where('quote_currency', $to);

        if ($asOf) {
            $query->where(function ($q) use ($asOf) {
                $q->whereNull('effective_at')
                    ->orWhere('effective_at', '<=', $asOf);
            });
        }

        $row = $query->orderByDesc('effective_at')->first();

        return $row ? (float) $row->rate : null;
    }

    private function fallbackRate(string $from, string $to): float
    {
        $base = $this->baseCurrency();
        $defaults = (array) config('billing.default_rates_from_usd', []);

        // Prefer JazzCash env for USD→PKR when DB has no row.
        if ($from === 'USD' && $to === 'PKR') {
            $jazz = config('services.jazzcash.usd_to_pkr_rate');
            if ($jazz !== null && (float) $jazz > 0) {
                return (float) $jazz;
            }
        }

        if ($from === $base && isset($defaults[$to]) && (float) $defaults[$to] > 0) {
            return (float) $defaults[$to];
        }

        if ($to === $base && isset($defaults[$from]) && (float) $defaults[$from] > 0) {
            return 1 / (float) $defaults[$from];
        }

        throw new RuntimeException("No FX rate configured for {$from} → {$to}. Set it under Super Admin → FX Rates.");
    }
}
