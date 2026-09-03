@extends('admin.layout.layout')

@section('title', 'Admin | Import result')

@section('admin-content')
    @php $s = $batch->summary ?? []; @endphp
    <div class="crm-page-header">
        <div>
            <h1>Import {{ str_replace('_', ' ', $batch->status) }}</h1>
            <p>{{ $batch->original_filename }} · {{ $batch->seller->name ?? '—' }}</p>
        </div>
        <a href="{{ route('admin.import.index') }}" class="btn btn-sm btn-outline-secondary">All imports</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            'Leads create' => $s['leads_create'] ?? 0,
            'Leads matched' => $s['leads_matched'] ?? 0,
            'Orders' => $s['orders'] ?? 0,
            'Pay links (real ID)' => $s['pay_links_real'] ?? 0,
            'Payments' => $s['payments'] ?? 0,
            'Payments without link' => $s['payments_without_link'] ?? 0,
            'Review flags' => $s['review_flags'] ?? 0,
        ] as $label => $count)
            <div class="col-6 col-md-3">
                <div class="crm-card h-100">
                    <div class="crm-card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-semibold">{{ $count }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($batch->status === 'committed')
        <form method="POST" action="{{ route('admin.import.rollback', $batch) }}" onsubmit="return confirm('Roll back this import? Imported rows will be removed.');">
            @csrf
            <button type="submit" class="btn btn-outline-danger">Roll back this import</button>
        </form>
    @endif

    @if (!empty($batch->decisions))
        <div class="crm-card mt-4">
            <div class="crm-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Decision</th>
                                <th>Email</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($batch->decisions, 0, 100) as $log)
                                <tr>
                                    <td>{{ $log['row'] ?? '' }}</td>
                                    <td>{{ $log['decision'] ?? '' }}</td>
                                    <td>{{ $log['email'] ?? '' }}</td>
                                    <td>{{ $log['reason'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
