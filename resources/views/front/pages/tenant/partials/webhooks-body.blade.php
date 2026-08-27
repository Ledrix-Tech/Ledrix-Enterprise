<div class="container py-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Outbound webhooks</h4>
            <p class="text-muted mb-0 small">Receive signed HTTP POSTs when subscription events happen for {{ $tenant->name }}</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('new_webhook_secret'))
        <div class="alert alert-warning">
            <strong>Copy this signing secret now — it will not be shown again.</strong>
            <input type="text" class="form-control mt-2 font-monospace" readonly
                value="{{ session('new_webhook_secret') }}" onclick="this.select()">
            <p class="small mb-0 mt-2">Verify with header <code>X-Ledrix-Signature: sha256=&lt;hmac&gt;</code> over the raw JSON body.</p>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">Add endpoint</div>
        <div class="card-body">
            <form method="POST" action="{{ org_route('webhooks.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" type="text" name="name" class="form-control" maxlength="100"
                        placeholder="Billing sync" value="{{ old('name') }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="url">HTTPS URL</label>
                    <input id="url" type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                        required maxlength="500" placeholder="https://example.com/webhooks/ledrix"
                        value="{{ old('url') }}">
                    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Events</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($availableEvents as $event)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="events[]" value="{{ $event }}"
                                    @checked(collect(old('events', $availableEvents))->contains($event))>
                                <span class="form-check-label"><code>{{ $event }}</code></span>
                            </label>
                        @endforeach
                    </div>
                    @error('events')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Create webhook</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Events</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($endpoints as $endpoint)
                            <tr>
                                <td>{{ $endpoint->name ?? '—' }}</td>
                                <td><small class="font-monospace">{{ \Illuminate\Support\Str::limit($endpoint->url, 48) }}</small></td>
                                <td>
                                    @foreach ($endpoint->events ?? [] as $ev)
                                        <span class="badge bg-light text-dark border">{{ $ev }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge bg-{{ $endpoint->enabled ? 'success' : 'secondary' }}">
                                        {{ $endpoint->enabled ? 'enabled' : 'disabled' }}
                                    </span>
                                    <br><small class="text-muted">{{ $endpoint->deliveries_count }} deliveries</small>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ org_route('webhooks.toggle', $endpoint->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                                            {{ $endpoint->enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ org_route('webhooks.destroy', $endpoint->id) }}" class="d-inline"
                                        onsubmit="return confirm('Delete this webhook endpoint?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No webhook endpoints yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
