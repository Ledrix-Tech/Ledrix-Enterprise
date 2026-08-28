@extends('clients.layouts.layout')

@section('title', 'Projects | Client Portal')

@section('client-content')
    <div class="crm-page-header">
        <div>
            <h1>Projects</h1>
            <p>Follow delivery status and progress after your order is paid.</p>
        </div>
    </div>

    @if ($awaitingOrders->isNotEmpty())
        <div class="crm-card mb-4">
            <div class="crm-card-header">
                <h2>Waiting to start</h2>
            </div>
            <div class="crm-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped crm-table mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Service</th>
                                <th>Order status</th>
                                <th>Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($awaitingOrders as $order)
                                <tr>
                                    <td data-label="Order">
                                        <a href="{{ route('client.invoice.details', $order) }}">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</a>
                                    </td>
                                    <td data-label="Service">{{ $order->service_name ?? '—' }}</td>
                                    <td data-label="Order status">@include('clients.includes.status-badge', ['status' => $order->status])</td>
                                    <td data-label="Delivery"><span class="text-muted">Kickoff pending</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="crm-card">
        <div class="crm-card-header">
            <h2>In delivery</h2>
        </div>
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped crm-table mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            @php $percent = $project->progressPercent(); @endphp
                            <tr>
                                <td data-label="Project">
                                    <a href="{{ route('client.projects.show', $project) }}" class="fw-semibold">{{ $project->title }}</a>
                                    @if ($project->projectManager?->name)
                                        <div class="text-muted small">PM {{ $project->projectManager->name }}</div>
                                    @endif
                                </td>
                                <td data-label="Order">
                                    {{ $project->order?->service_name ?? ('#'.$project->order_id) }}
                                    @if ($project->order)
                                        <div class="small">@include('clients.includes.status-badge', ['status' => $project->order->status])</div>
                                    @endif
                                </td>
                                <td data-label="Status">@include('clients.includes.status-badge', ['status' => $project->status])</td>
                                <td data-label="Progress" style="min-width:140px;">
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-muted small mt-1">{{ $percent }}%
                                        @if ($project->tasks->isNotEmpty())
                                            · {{ $project->completedTasksCount() }}/{{ $project->tasks->count() }} tasks
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Due">{{ $project->due_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-kanban d-block fs-1 mb-2 text-secondary"></i>
                                    No delivery projects yet. After an order is paid, your team will open a project and progress will show here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($projects->hasPages())
                <div class="crm-pagination p-3">{{ $projects->links() }}</div>
            @endif
        </div>
    </div>
@endsection
