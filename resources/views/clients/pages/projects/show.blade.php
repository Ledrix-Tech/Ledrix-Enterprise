@extends('clients.layouts.layout')

@section('title', $project->title.' | Client Portal')

@section('client-content')
    @php
        $order = $project->order;
        $percent = $project->progressPercent();
        $orderStage = match (true) {
            in_array($order?->status, ['completed'], true) || $project->status === 'completed' => 4,
            in_array($project->status, ['in_progress', 'completed'], true) || in_array($order?->status, ['in_progress', 'revision'], true) => 3,
            filled($order?->paid_at) || in_array($order?->status, ['paid', 'in_progress', 'revision', 'completed'], true) => 2,
            default => 1,
        };
        $steps = [
            1 => 'Order placed',
            2 => 'Paid',
            3 => 'In delivery',
            4 => 'Completed',
        ];
    @endphp

    <div class="crm-page-header">
        <div>
            <a href="{{ route('client.projects.index') }}" class="crm-back"><i class="bi bi-arrow-left"></i> Back to projects</a>
            <h1>{{ $project->title }}</h1>
            <p>
                {{ $order?->service_name ?? 'Delivery project' }}
                @if ($project->projectManager?->name)
                    · {{ $project->projectManager->name }}
                @endif
            </p>
        </div>
        <div class="crm-page-actions">
            @include('clients.includes.status-badge', ['status' => $project->status])
        </div>
    </div>

    <div class="crm-card mb-4">
        <div class="crm-card-body">
            <h2 class="h6 mb-3">Order progress</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($steps as $n => $label)
                    <span class="badge rounded-pill {{ $orderStage >= $n ? 'text-bg-success' : 'text-bg-light text-muted' }} px-3 py-2">
                        {{ $n }}. {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="crm-card h-100">
                <div class="crm-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 mb-0">Delivery progress</h2>
                        <span class="fw-semibold">{{ $percent }}%</span>
                    </div>
                    <div class="progress mb-2" style="height:10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mb-0">
                        @if ($project->tasks->isNotEmpty())
                            {{ $project->completedTasksCount() }} of {{ $project->tasks->count() }} tasks complete
                        @else
                            Status: {{ \Illuminate\Support\Str::headline($project->status) }}
                        @endif
                    </p>
                    @if ($project->description)
                        <p class="mt-3 mb-0">{{ $project->description }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="crm-card h-100">
                <div class="crm-card-body">
                    <p class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Started</span>
                        <span>{{ $project->start_date?->format('M d, Y') ?? '—' }}</span>
                    </p>
                    <p class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Due</span>
                        <span>{{ $project->due_date?->format('M d, Y') ?? '—' }}</span>
                    </p>
                    <p class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Order</span>
                        <span>@include('clients.includes.status-badge', ['status' => $order?->status ?? 'pending'])</span>
                    </p>
                    @if ($order)
                        <a href="{{ route('client.invoice.details', $order) }}" class="btn btn-sm btn-crm-outline w-100 mt-2">View invoice</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-header">
            <h2>Tasks</h2>
        </div>
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table crm-table mb-0">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->tasks as $task)
                            <tr>
                                <td data-label="Task">{{ $task->title }}</td>
                                <td data-label="Status">@include('clients.includes.status-badge', ['status' => $task->status])</td>
                                <td data-label="Due">{{ $task->due_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted text-center py-4">Tasks will appear here as the team breaks down the work.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
