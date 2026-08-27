@extends('central.layout.layout')

@section('title', 'Ledrix | FX Rates')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>FX Rates</h1>
            <p>
                Convert plan list prices (USD) into tenant currencies and normalize MRR/ARR into
                <strong>{{ $baseCurrency }}</strong>. Pakistan uses Meezan (PKR); other countries use Stripe
                (USD / AED / …).
            </p>
        </div>
        <a href="{{ route('super-admin.billing-settings.get') }}" class="btn btn-outline-primary btn-sm">Payment Accounts</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($migrationRequired)
        <div class="alert alert-warning">
            <strong>Migration required.</strong>
            Run:
            <code>php artisan migrate --database=central --path=database/migrations/central --force</code>
        </div>
    @else
        <div class="sa-card mb-4">
            <div class="sa-card-header"><h4 class="mb-0">Add / update rate</h4></div>
            <div class="sa-card-body">
                <p class="small text-muted mb-3">
                    Rate = how many <em>quote</em> units equal <strong>1</strong> <em>base</em> unit
                    (e.g. USD→PKR = 280 means 1 USD = 280 PKR).
                </p>
                <form method="POST" action="{{ route('super-admin.fx-rates.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label" for="base_currency">Base</label>
                        <select id="base_currency" name="base_currency" class="form-select" required>
                            @foreach ($supportedCurrencies as $ccy)
                                <option value="{{ $ccy }}" @selected(old('base_currency', $baseCurrency) === $ccy)>{{ $ccy }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="quote_currency">Quote</label>
                        <select id="quote_currency" name="quote_currency" class="form-select" required>
                            @foreach ($supportedCurrencies as $ccy)
                                <option value="{{ $ccy }}" @selected(old('quote_currency', 'PKR') === $ccy)>{{ $ccy }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="rate">Rate</label>
                        <input id="rate" type="number" step="any" min="0.00000001" name="rate"
                            class="form-control" value="{{ old('rate') }}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sa-primary w-100">Save rate</button>
                    </div>
                </form>
                @if (! empty($defaults))
                    <p class="small text-muted mb-0 mt-3">
                        Config fallbacks (when no row):
                        @foreach ($defaults as $q => $r)
                            1 {{ $baseCurrency }} = {{ $r }} {{ $q }}@if (! $loop->last); @endif
                        @endforeach
                    </p>
                @endif
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h4 class="mb-0">Stored rates</h4></div>
            <div class="sa-card-body p-0">
                <div class="sa-table-wrap">
                    <table class="table sa-table mb-0">
                        <thead>
                            <tr>
                                <th>Pair</th>
                                <th>Rate</th>
                                <th>Source</th>
                                <th>Effective</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rates as $row)
                                <tr>
                                    <td><code>{{ $row->base_currency }} → {{ $row->quote_currency }}</code></td>
                                    <td>{{ rtrim(rtrim(number_format((float) $row->rate, 8, '.', ''), '0'), '.') }}</td>
                                    <td>{{ $row->source }}</td>
                                    <td>{{ $row->effective_at?->format('M d, Y H:i') ?? '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('super-admin.fx-rates.destroy') }}"
                                            onsubmit="return confirm('Remove this FX rate?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="base_currency" value="{{ $row->base_currency }}">
                                            <input type="hidden" name="quote_currency" value="{{ $row->quote_currency }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-4">No rates stored yet — defaults were seeded on first visit.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
