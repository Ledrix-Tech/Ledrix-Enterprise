@php
    $class = match ($status) {
        'paid', 'completed', 'success' => 'crm-status-success',
        'in_progress', 'revision' => 'crm-status-info',
        'pending', 'open', 'blocked' => 'crm-status-warning',
        'canceled', 'cancelled' => 'crm-status-danger',
        default => 'crm-status-neutral',
    };
@endphp
<span class="crm-status {{ $class }}">{{ \Illuminate\Support\Str::headline((string) $status) }}</span>
