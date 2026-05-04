<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request, OrderService $service): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $orderRef = $service->submit(
            $request->integer('user_id'),
            $request->input('items')
        );

        return response()->json([
            'order_ref' => $orderRef,
            'status' => 'queued',
            'message' => 'Order accepted for processing',
        ], 202);
    }

    public function show(string $orderRef): JsonResponse
    {
        $order = Order::where('order_ref', $orderRef)
            ->with('items.product')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }
}
