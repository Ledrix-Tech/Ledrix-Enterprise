<?php

namespace App\Http\Controllers\API\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Support\ClientPortalAuthorization;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $client = ClientPortalAuthorization::client();

        $orders = Order::query()
            ->with(['seller:id,name', 'brand:id,brand_name'])
            ->where('client_id', $client->id)
            ->whereNotNull('seller_id')
            ->latest('id')
            ->get(['id', 'service_name', 'status', 'seller_id', 'brand_id', 'client_id']);

        $selectedOrderId = (int) $request->query('order');
        $selectedOrder = null;
        $messages = collect();

        if ($selectedOrderId > 0) {
            $selectedOrder = $orders->firstWhere('id', $selectedOrderId);
            if ($selectedOrder) {
                $messages = OrderMessage::query()
                    ->where('order_id', $selectedOrder->id)
                    ->where('client_id', $client->id)
                    ->orderBy('created_at')
                    ->get();

                OrderMessage::query()
                    ->where('order_id', $selectedOrder->id)
                    ->where('client_id', $client->id)
                    ->where('sender_type', 'seller')
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }
        }

        return view('clients.pages.messages', compact('client', 'orders', 'selectedOrder', 'messages'));
    }

    public function store(Request $request, Order $order)
    {
        $client = ClientPortalAuthorization::client();
        ClientPortalAuthorization::assertOwnsOrder($order);
        abort_unless($order->seller_id, 422, 'This order has no assigned seller yet.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        OrderMessage::query()->create([
            'order_id'    => $order->id,
            'client_id'   => $client->id,
            'seller_id'   => $order->seller_id,
            'sender_type' => 'client',
            'sender_id'   => $client->id,
            'body'        => trim($data['body']),
        ]);

        return redirect()
            ->route('client.messages.get', ['order' => $order->id])
            ->with('success', 'Message sent.');
    }
}
