<div class="crm-page-header">
    <div>
        <h1>Projects</h1>
        <p>Track delivery work linked to CRM orders</p>
    </div>
    @if ($canCreate ?? false)
        <button type="button" class="btn btn-crm-teal" data-toggle="modal" data-target="#addProject">
            <i class="bi bi-plus-lg me-1"></i> New project
        </button>
    @endif
</div>

<div class="crm-card">
    <div class="crm-card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Order</th>
                        <th>PM</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th>Tasks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ $showRoute($project) }}" class="fw-semibold">{{ $project->title }}</a>
                            </td>
                            <td>{{ $project->order?->service_name ?? ('#'.$project->order_id) }}</td>
                            <td>{{ $project->projectManager?->name ?? '—' }}</td>
                            <td><span class="crm-status crm-status-info">{{ Str::headline($project->status) }}</span></td>
                            <td>{{ $project->due_date?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $project->tasks->count() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No projects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($projects->hasPages())
            <div class="crm-pagination">{{ $projects->links() }}</div>
        @endif
    </div>
</div>

@if ($canCreate ?? false)
    <div class="modal fade" id="addProject" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ $storeRoute }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New project</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Order</label>
                        <select name="order_id" class="form-control" required>
                            <option value="">Select order</option>
                            @foreach ($orders as $order)
                                <option value="{{ $order->id }}">
                                    #{{ $order->id }} {{ $order->service_name }}
                                    @if ($order->lead?->client?->name) — {{ $order->lead->client->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        @if ($orders->isEmpty())
                            <small class="text-muted">Create an order first, then attach a project.</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Project manager</label>
                        <select name="owner_seller_id" class="form-control" required>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected(auth('seller')->id() === $seller->id)>
                                    {{ $seller->name }} ({{ Str::headline($seller->is_seller) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Front seller (optional)</label>
                        <select name="front_seller_id" class="form-control">
                            <option value="">Same as PM</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start</label>
                            <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-crm-teal" @disabled($orders->isEmpty())>Create</button>
                </div>
            </form>
        </div>
    </div>
@endif
