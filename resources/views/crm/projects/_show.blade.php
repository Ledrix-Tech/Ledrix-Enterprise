<div class="crm-page-header">
    <div>
        <a href="{{ $indexRoute }}" class="small text-muted">&larr; Projects</a>
        <h1>{{ $project->title }}</h1>
        <p>
            {{ $project->order?->service_name ?? 'No order title' }}
            · PM {{ $project->projectManager?->name ?? '—' }}
        </p>
    </div>
</div>

@if ($canMutate)
    <div class="crm-card mb-4">
        <div class="crm-card-body">
            <form method="POST" action="{{ $updateRoute }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ $project->title }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            @foreach (['pending', 'in_progress', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected($project->status === $status)>{{ Str::headline($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Due</label>
                        <input type="date" name="due_date" class="form-control" value="{{ $project->due_date?->toDateString() }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project manager</label>
                        <select name="owner_seller_id" class="form-control" required>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected((int) $project->owner_seller_id === (int) $seller->id)>{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Front seller</label>
                        <select name="front_seller_id" class="form-control">
                            <option value="">—</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected((int) $project->front_seller_id === (int) $seller->id)>{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $project->start_date?->toDateString() }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $project->description }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-crm-teal">Save project</button>
            </form>
            <form method="POST" action="{{ $destroyRoute }}" class="mt-3" onsubmit="return confirm('Delete this project and its tasks?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete project</button>
            </form>
        </div>
    </div>
@endif

<div class="crm-card">
    <div class="crm-card-body">
        <h5 class="mb-3">Tasks</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assignee</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($project->tasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->assignedSeller?->name ?? '—' }}</td>
                            <td>{{ Str::headline($task->priority) }}</td>
                            <td>
                                @php
                                    $canStatus = $canMutate || (auth('seller')->id() && (int) $task->assigned_to === (int) auth('seller')->id());
                                @endphp
                                @if ($canStatus)
                                    <form method="POST" action="{{ $updateTaskRoute($task) }}" class="d-flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        @if ($canMutate)
                                            <input type="hidden" name="title" value="{{ $task->title }}">
                                            <input type="hidden" name="description" value="{{ $task->description }}">
                                            <input type="hidden" name="assigned_to" value="{{ $task->assigned_to }}">
                                            <input type="hidden" name="priority" value="{{ $task->priority }}">
                                            <input type="hidden" name="due_date" value="{{ $task->due_date?->toDateString() }}">
                                        @endif
                                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                            @foreach (['pending', 'in_progress', 'completed', 'blocked'] as $st)
                                                <option value="{{ $st }}" @selected($task->status === $st)>{{ Str::headline($st) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    {{ Str::headline($task->status) }}
                                @endif
                            </td>
                            <td>
                                @if ($canMutate)
                                    <form method="POST" action="{{ $destroyTaskRoute($task) }}" onsubmit="return confirm('Remove this task?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">No tasks yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canMutate)
            <hr>
            <form method="POST" action="{{ $storeTaskRoute }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="title" class="form-control" placeholder="Task title" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In progress</option>
                            <option value="blocked">Blocked</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-2">
                        <button class="btn btn-crm-teal w-100">Add</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
