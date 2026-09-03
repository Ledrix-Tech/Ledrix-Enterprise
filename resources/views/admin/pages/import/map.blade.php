@extends('admin.layout.layout')

@section('title', 'Admin | Map columns')

@section('admin-content')
    <div class="crm-page-header">
        <div>
            <h1>Map columns</h1>
            <p>
                File: <strong>{{ $batch->original_filename }}</strong>.
                Unmapped columns are ignored — they are not errors.
            </p>
        </div>
        <a href="{{ route('admin.import.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    @if (session('import_limit'))
        <div class="alert alert-warning">
            <p class="mb-1 fw-semibold">{{ session('import_limit')['headline'] }}</p>
            <p class="mb-1">{{ session('import_limit')['workaround'] }}</p>
            <p class="mb-0">
                <a href="{{ session('import_limit')['upgrade_url'] }}">{{ session('import_limit')['upgrade'] }}</a>
            </p>
        </div>
    @endif

    <div class="crm-card">
        <div class="crm-card-body">
            <form method="POST" action="{{ route('admin.import.map.save', $batch) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Your column</th>
                                <th>Sample values</th>
                                <th>Maps to</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($headers as $header)
                                <tr>
                                    <td><strong>{{ $header }}</strong></td>
                                    <td class="text-muted">{{ implode(' · ', $samples[$header] ?? []) ?: '—' }}</td>
                                    <td>
                                        <select name="mapping[{{ $header }}]" class="form-select">
                                            @foreach ($targets as $value => $label)
                                                <option value="{{ $value }}" @selected(($mapping[$header] ?? 'ignore') === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-crm-teal">Preview import</button>
            </form>
        </div>
    </div>
@endsection
