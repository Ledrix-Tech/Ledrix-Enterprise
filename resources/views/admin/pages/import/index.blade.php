@extends('admin.layout.layout')

@section('title', 'Admin | Import sheet')

@section('admin-content')
    <div class="crm-page-header">
        <div>
            <h1>Import historical sales</h1>
            <p>Upload a CSV of past clients, orders, and payments. You do not need Ledrix column names — map whatever headers you have on the next screen.</p>
        </div>
        <div class="crm-page-actions">
            <a href="{{ route('admin.import.guide') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf me-1"></i> How import works (PDF)
            </a>
            <a href="{{ route('admin.import.sample') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Download sample CSV
            </a>
        </div>
    </div>

    @php $quota = $importQuota ?? []; @endphp

    @if (session('import_limit'))
        <div class="alert alert-warning">
            <p class="mb-1 fw-semibold">{{ session('import_limit')['headline'] }}</p>
            <p class="mb-1">{{ session('import_limit')['workaround'] }}</p>
            <p class="mb-0">
                <a href="{{ session('import_limit')['upgrade_url'] }}">{{ session('import_limit')['upgrade'] }}</a>
            </p>
        </div>
    @endif

    <div class="crm-card mb-4">
        <div class="crm-card-body">
            @if (! empty($quota))
                <p class="small text-muted mb-2">
                    Imports this cycle:
                    <strong>{{ (int) ($quota['uploads_used'] ?? 0) }}</strong>@if (($quota['uploads_max'] ?? null) !== null)/{{ (int) $quota['uploads_max'] }}@else (unlimited)@endif
                    @if (! empty($quota['rows_max']))
                        · up to {{ number_format((int) $quota['rows_max']) }} rows per file
                    @else
                        · unlimited rows per file
                    @endif
                    @if (! empty($quota['reset_on']))
                        · resets {{ $quota['reset_on'] }}
                    @endif
                </p>
            @endif
            <p class="text-muted mb-3">
                Excel users: <strong>File → Save As → CSV UTF-8</strong>. The sample file covers contacts-only, invoiced-but-unpaid, and cash/check rows. Leave cells blank when that data does not exist — empty cells are not filled in with fake IDs.
            </p>

            @if ($brands->isEmpty() || $sellers->isEmpty())
                <div class="alert alert-warning mb-0">
                    @if ($brands->isEmpty())
                        Add a brand first.
                    @endif
                    @if ($sellers->isEmpty())
                        Add a seller first — imported leads must have an owner.
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select" {{ old('multi_brand') ? 'disabled' : '' }} id="importBrand">
                                <option value="">Select brand</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected((string) old('brand_id') === (string) $brand->id)>
                                        {{ $brand->brand_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="multi_brand" value="1" id="multiBrand"
                                    @checked(old('multi_brand'))
                                    @disabled(empty($quota['multi_brand']))>
                                <label class="form-check-label" for="multiBrand">
                                    Sheet spans multiple brands (requires a brand_name column)
                                </label>
                            </div>
                            @if (empty($quota['multi_brand']))
                                <small class="text-muted">Your plan is single-brand. Split the sheet by brand, or upgrade for multi-brand imports.</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign to seller</label>
                            <select name="seller_id" class="form-select" required>
                                <option value="">Historical owner</option>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}" @selected((string) old('seller_id') === (string) $seller->id)>
                                        {{ $seller->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Required. Leads always belong to a seller.</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="enter_live_pipeline" value="1" id="livePipeline" @checked(old('enter_live_pipeline'))>
                                <label class="form-check-label" for="livePipeline">
                                    Enter live pipeline (assign leads and allow routing). Leave off for historical records.
                                </label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">CSV file</label>
                            <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-crm-teal w-100">Upload and map columns</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Brand</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Rows</th>
                            <th>When</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td>{{ $batch->original_filename }}</td>
                                <td>{{ $batch->multi_brand ? 'Multiple' : ($batch->brand->brand_name ?? '—') }}</td>
                                <td>{{ $batch->seller->name ?? '—' }}</td>
                                <td><span class="crm-status">{{ str_replace('_', ' ', $batch->status) }}</span></td>
                                <td>{{ $batch->row_count }}</td>
                                <td>{{ $batch->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if ($batch->status === 'committed' || $batch->status === 'rolled_back')
                                        <a href="{{ route('admin.import.show', $batch) }}">View</a>
                                    @elseif ($batch->status === 'previewed' || $batch->status === 'mapped')
                                        <a href="{{ route('admin.import.preview', $batch) }}">Preview</a>
                                    @else
                                        <a href="{{ route('admin.import.map', $batch) }}">Map</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crm-empty">
                                        <i class="bi bi-upload d-block"></i>
                                        No imports yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($batches->hasPages())
                <div class="crm-pagination">{{ $batches->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const box = document.getElementById('multiBrand');
        const brand = document.getElementById('importBrand');
        if (!box || !brand) return;
        const sync = () => { brand.disabled = box.checked; };
        box.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
