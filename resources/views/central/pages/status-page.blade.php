@extends('central.layout.layout')

@section('title', 'Ledrix | Status Page')

@section('central-content')
    @php $canManage = auth('super_admin')->user()?->isAdmin() ?? false; @endphp
    <div class="sa-page-header">
        <div>
            <h1>Public Status Page</h1>
            <p>Component health and incidents shown at <a href="{{ route('status.get') }}" target="_blank" rel="noopener">/status</a>. Overall: <strong>{{ $overallLabel }}</strong></p>
        </div>
        @if ($canManage && ! $migrationRequired)
            <button type="button" class="btn btn-sa-primary" data-bs-toggle="modal" data-bs-target="#addIncident">
                <i class="bi bi-plus-lg me-1"></i> New incident
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($migrationRequired)
        <div class="alert alert-warning">
            Run central migrations first:
            <code>php artisan migrate --database=central --path=database/migrations/central --force</code>
        </div>
    @else
        <div class="sa-card mb-4">
            <div class="sa-card-body">
                <h2 class="h5 mb-3">Components</h2>
                <div class="sa-table-wrap">
                    <table class="table sa-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Note</th>
                                @if ($canManage)<th>Action</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($components as $component)
                                <tr>
                                    <td data-label="Name"><strong>{{ $component->name }}</strong><br><small class="text-muted">{{ $component->key }}</small></td>
                                    <td data-label="Status"><code>{{ $component->status }}</code></td>
                                    <td data-label="Note">{{ $component->description ?: '—' }}</td>
                                    @if ($canManage)
                                        <td data-label="Action">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editComponent{{ $component->id }}">Edit</button>
                                        </td>
                                    @endif
                                </tr>

                                @if ($canManage)
                                    <div class="modal fade" id="editComponent{{ $component->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('super-admin.status.components.update', $component->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update {{ $component->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select" required>
                                                                @foreach (\App\Models\Central\PlatformStatusComponent::STATUSES as $st)
                                                                    <option value="{{ $st }}" @selected($component->status === $st)>{{ str_replace('_', ' ', $st) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-0">
                                                            <label class="form-label">Public note</label>
                                                            <input type="text" name="description" class="form-control" maxlength="500"
                                                                value="{{ $component->description }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sa-primary">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-body p-0">
                <div class="p-3 border-bottom"><h2 class="h5 mb-0">Incidents</h2></div>
                <div class="sa-table-wrap">
                    <table class="table sa-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($incidents as $incident)
                                <tr>
                                    <td data-label="#">{{ $incident->id }}</td>
                                    <td data-label="Title">
                                        <strong>{{ $incident->title }}</strong>
                                        @if ($incident->body)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($incident->body, 80) }}</small>
                                        @endif
                                    </td>
                                    <td data-label="Severity">{{ $incident->severity }}</td>
                                    <td data-label="Status">{{ $incident->status }}</td>
                                    <td data-label="Started">{{ $incident->started_at?->format('M j, Y H:i') ?? '—' }}</td>
                                    <td data-label="Action">
                                        @if ($canManage)
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editIncident{{ $incident->id }}">Edit</button>
                                            <form method="POST" action="{{ route('super-admin.status.incidents.destroy', $incident->id) }}"
                                                class="d-inline" onsubmit="return confirm('Delete this incident?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @else
                                            <span class="text-muted">View only</span>
                                        @endif
                                    </td>
                                </tr>

                                @if ($canManage)
                                    <div class="modal fade" id="editIncident{{ $incident->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('super-admin.status.incidents.update', $incident->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit incident</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @include('central.pages.partials.status-incident-form', ['incident' => $incident])
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sa-primary">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No incidents yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($incidents->hasPages())
                    <div class="sa-pagination">{{ $incidents->links() }}</div>
                @endif
            </div>
        </div>

        @if ($canManage)
            <div class="modal fade" id="addIncident" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('super-admin.status.incidents.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">New incident</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                @include('central.pages.partials.status-incident-form', ['incident' => null])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sa-primary">Publish</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection
