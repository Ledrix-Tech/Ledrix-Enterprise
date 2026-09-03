@extends('admin.layout.layout')

@section('title', 'Admin | Import preview')

@section('admin-content')
    @php $s = $plan['summary'] ?? []; @endphp
    <div class="crm-page-header">
        <div>
            <h1>Preview import</h1>
            <p>Nothing has been written yet. Counts below are what commit will create.</p>
        </div>
        <a href="{{ route('admin.import.map', $batch) }}" class="btn btn-sm btn-outline-secondary">Edit mapping</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            'Rows' => $s['rows'] ?? 0,
            'Leads create' => $s['leads_create'] ?? 0,
            'Leads matched' => $s['leads_matched'] ?? 0,
            'Orders' => $s['orders'] ?? 0,
            'Pay links (real ID)' => $s['pay_links_real'] ?? 0,
            'Pay links unknown' => $s['pay_links_unknown'] ?? 0,
            'Payments' => $s['payments'] ?? 0,
            'Payments without link' => $s['payments_without_link'] ?? 0,
            'Review flags' => $s['review_flags'] ?? 0,
            'Unmatched brands' => $s['unmatched_brands'] ?? 0,
            'Duplicates' => $s['duplicates'] ?? 0,
        ] as $label => $count)
            <div class="col-6 col-md-3 col-xl-2">
                <div class="crm-card h-100">
                    <div class="crm-card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-semibold">{{ $count }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if (!empty($plan['unmatched_brand_rows']))
        <div class="crm-card mb-4">
            <div class="crm-card-body">
                <h2 class="h5">Unmatched brands</h2>
                <p class="text-muted">These rows will not be imported. They are not dropped silently.</p>
                <ul class="mb-0">
                    @foreach ($plan['unmatched_brand_rows'] as $row)
                        <li>Row {{ $row['row'] }}: {{ $row['brand_name'] ?: '(empty)' }} — {{ $row['reason'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (!empty($plan['duplicate_rows']))
        <div class="crm-card mb-4">
            <div class="crm-card-body">
                <h2 class="h5">Duplicate contacts</h2>
                <p class="text-muted">Same brand + email or phone already exists. Choose one action for the whole batch.</p>
                <ul>
                    @foreach (array_slice($plan['duplicate_rows'], 0, 20) as $row)
                        <li>Row {{ $row['row'] }} {{ $row['email'] }} matches lead #{{ $row['existing_id'] }} ({{ $row['existing_name'] }})</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="crm-card">
        <div class="crm-card-body">
            <form method="POST" action="{{ route('admin.import.commit', $batch) }}">
                @csrf
                <label class="form-label">If a contact already exists</label>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="duplicate_strategy" id="dupMerge" value="merge" @checked(($s['duplicates'] ?? 0) === 0)>
                        <label class="form-check-label" for="dupMerge">Merge — add orders/payments onto the existing lead</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="duplicate_strategy" id="dupSkip" value="skip" @checked(($s['duplicates'] ?? 0) > 0) required>
                        <label class="form-check-label" for="dupSkip">Skip — leave the existing lead alone</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="duplicate_strategy" id="dupNew" value="create_new">
                        <label class="form-check-label" for="dupNew">Create new — import as a separate lead anyway</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-crm-teal">Commit import</button>
            </form>
        </div>
    </div>
@endsection
