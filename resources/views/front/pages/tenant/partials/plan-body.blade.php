<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Plan &amp; features</h4>
            <p class="text-muted mb-0 small">
                {{ $plan?->name ?? 'No plan' }}
                · read-only view of what’s included
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
            <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">Billing</a>
            <a href="{{ route('pricing.get') }}" class="btn btn-primary btn-sm" target="_blank">Compare plans</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach ($limits as $row)
            @php
                $max = $row['max'];
                $unlimited = $max === null || (int) $max === -1;
            @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fw-bold fs-5">
                        {{ (int) $row['used'] }}@if (! $unlimited)<span class="fs-6 text-muted fw-normal">/{{ (int) $max }}</span>@endif
                    </div>
                    <div class="small text-muted">{{ $row['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Feature matrix</div>
        <div class="card-body p-0">
            @forelse ($featureMatrix as $group => $rows)
                <div class="px-3 pt-3"><strong class="text-uppercase small text-muted">{{ $group }}</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Feature</th>
                                <th class="text-center">Included</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $feature)
                                <tr>
                                    <td>
                                        {{ $feature['label'] ?? ($feature['key'] ?? 'Feature') }}
                                        @if (! empty($feature['description']))
                                            <br><small class="text-muted">{{ $feature['description'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($feature['effective'] ?? false)
                                            <span class="badge bg-success">On</span>
                                        @else
                                            <span class="badge bg-secondary">Off</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-muted p-3 mb-0">No feature definitions available.</p>
            @endforelse
        </div>
    </div>

    <div class="alert alert-info mt-4 mb-0">
        Need a higher plan?
        <a href="{{ route('pricing.get') }}" class="alert-link">View pricing</a>
        or change below.
    </div>

    @if (! empty($pendingPlanChange['plan_name']))
        <div class="alert alert-warning mt-3">
            Pending plan change to <strong>{{ $pendingPlanChange['plan_name'] }}</strong>
            (applies at period end).
        </div>
    @endif

    @if (! empty($availablePlans))
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header">Change plan</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($availablePlans as $option)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold">{{ $option->name }}</div>
                                <div class="small text-muted mb-2">
                                    ${{ number_format((float) $option->monthly_price, 2) }}/mo
                                    @if ((int) $option->id === (int) ($plan?->id))
                                        · <span class="text-success">Current</span>
                                    @endif
                                </div>
                                @if ((int) $option->id !== (int) ($plan?->id))
                                    <form method="POST" action="{{ org_route('plan.change') }}" class="d-grid gap-2">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $option->id }}">
                                        <button type="submit" name="timing" value="period_end" class="btn btn-sm btn-outline-primary">
                                            Switch at period end
                                        </button>
                                        <button type="submit" name="timing" value="immediate" class="btn btn-sm btn-primary"
                                            onclick="return confirm('Immediate upgrade charges a prorated invoice now. Continue?')">
                                            Upgrade now (prorate)
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="small text-muted mb-0 mt-3">
                    Downgrades always apply at period end. Immediate is for upgrades only (Stripe USD / Meezan PKR).
                </p>
            </div>
        </div>
    @endif
</div>
