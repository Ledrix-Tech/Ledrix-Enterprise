<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\Seller;
use App\Support\PortalAuthorization;
use Illuminate\Http\Request;

class SellerMessageController extends Controller
{
    public function index(Request $request)
    {
        $actor = PortalAuthorization::requirePortalActor();

        $ordersQuery = Order::query()
            ->with(['client:id,name,email', 'brand:id,brand_name'])
            ->whereNotNull('client_id');

        if ($actor instanceof Seller) {
            $ordersQuery->where(function ($q) use ($actor) {
                $q->where('seller_id', $actor->id)
                    ->orWhere('owner_seller_id', $actor->id);
            });
        }

        $orders = $ordersQuery
            ->withCount([
                'messages as unread_count' => fn ($q) => $q
                    ->where('sender_type', 'client')
                    ->whereNull('read_at'),
            ])
            ->latest('id')
            ->get(['id', 'service_name', 'status', 'seller_id', 'client_id', 'brand_id']);

        $selectedOrderId = (int) $request->query('order');
        $selectedOrder = null;
        $messages = collect();

        if ($selectedOrderId > 0) {
            $selectedOrder = Order::query()->findOrFail($selectedOrderId);
            PortalAuthorization::authorizeOrder($selectedOrder);

            $messages = OrderMessage::query()
                ->where('order_id', $selectedOrder->id)
                ->orderBy('created_at')
                ->get();

            OrderMessage::query()
                ->where('order_id', $selectedOrder->id)
                ->where('sender_type', 'client')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('sellers.pages.messages', compact('orders', 'selectedOrder', 'messages'));
    }

    public function store(Request $request, Order $order)
    {
        $actor = PortalAuthorization::requirePortalActor();
        PortalAuthorization::authorizeOrder($order);
        abort_unless($order->client_id, 422, 'This order has no client.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        OrderMessage::query()->create([
            'order_id'    => $order->id,
            'client_id'   => $order->client_id,
            'seller_id'   => $order->seller_id,
            'sender_type' => 'seller',
            'sender_id'   => $actor->id,
            'body'        => trim($data['body']),
        ]);

        return redirect()
            ->route('seller.messages.get', ['order' => $order->id])
            ->with('success', 'Message sent.');
    }
}
