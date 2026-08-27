@extends('sellers.layout.layout')

@section('title', 'Messages | Seller')

@section('sellers-content')
    <div class="crm-page-header">
        <div>
            <h1>Messages</h1>
            <p>Order conversations with your clients.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="crm-card">
                <div class="crm-card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($orders as $order)
                            <a href="{{ route('seller.messages.get', ['order' => $order->id]) }}"
                                class="list-group-item list-group-item-action {{ ($selectedOrder?->id === $order->id) ? 'active' : '' }}">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">#{{ $order->id }} · {{ $order->client?->name ?? 'Client' }}</div>
                                    @if (($order->unread_count ?? 0) > 0)
                                        <span class="badge bg-danger">{{ $order->unread_count }}</span>
                                    @endif
                                </div>
                                <small class="{{ ($selectedOrder?->id === $order->id) ? '' : 'text-muted' }}">
                                    {{ $order->service_name ?: 'Order' }} · {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </small>
                            </a>
                        @empty
                            <div class="p-3 text-muted">No client orders available for messaging.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="crm-card">
                <div class="crm-card-body">
                    @if (! $selectedOrder)
                        <p class="text-muted mb-0">Select an order to open the thread.</p>
                    @else
                        <h2 class="h5 mb-3">Order #{{ $selectedOrder->id }} · {{ $selectedOrder->client?->name ?? 'Client' }}</h2>
                        <div class="border rounded p-3 mb-3" style="max-height: 420px; overflow-y: auto; background: #f8fafc;">
                            @forelse ($messages as $message)
                                <div class="mb-3 {{ $message->isFromSeller() ? 'text-end' : '' }}">
                                    <div class="d-inline-block text-start px-3 py-2 rounded {{ $message->isFromSeller() ? 'bg-primary text-white' : 'bg-white border' }}" style="max-width: 85%;">
                                        <div class="small opacity-75 mb-1">
                                            {{ $message->isFromSeller() ? 'You' : 'Client' }}
                                            · {{ $message->created_at?->format('M j, g:i A') }}
                                        </div>
                                        <div style="white-space: pre-wrap;">{{ $message->body }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No messages yet.</p>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('seller.messages.store', $selectedOrder) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label" for="sellerMessageBody">Message</label>
                                <textarea id="sellerMessageBody" name="body" rows="3" class="form-control" required maxlength="5000"
                                    placeholder="Write a reply…">{{ old('body') }}</textarea>
                                @error('body')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-crm-primary">Send</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
