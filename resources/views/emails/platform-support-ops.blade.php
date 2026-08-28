<x-mail::message>
@if ($event === 'tenant_replied')
# Tenant replied on ticket #{{ $ticket->id }}
@else
# New support ticket #{{ $ticket->id }}
@endif

**Tenant:** {{ $ticket->tenant?->name ?? '—' }} ({{ $ticket->tenant?->email ?? '—' }})
**Priority:** {{ $ticket->priority }}
**Category:** {{ $ticket->category }}
**Subject:** {{ $ticket->subject }}

@if ($event === 'tenant_replied' && $replyMessage)
**Latest tenant reply**

{{ \Illuminate\Support\Str::limit($replyMessage, 1500) }}
@else
{{ \Illuminate\Support\Str::limit((string) $ticket->description, 1500) }}
@endif

<x-mail::button :url="$url">
Open ticket
</x-mail::button>
</x-mail::message>
