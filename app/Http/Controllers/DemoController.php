<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOrder;
use App\Models\BatchFailedRecord;
use App\Models\JobMetric;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoController extends Controller
{
    public function race(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'concurrency' => 'required|integer|min:2|max:50',
            'set_stock' => 'nullable|integer|min:0',
        ]);

        $productId = $request->integer('product_id');
        $concurrency = $request->integer('concurrency');
        $setStock = $request->input('set_stock');

        if ($setStock !== null) {
            Product::where('id', $productId)->update(['stock' => $setStock]);
        }

        $dispatched = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $orderRef = Str::uuid()->toString();
            ProcessOrder::dispatch($orderRef, 1, [
                ['product_id' => $productId, 'quantity' => 1],
            ]);
            $dispatched[] = $orderRef;
        }

        return response()->json([
            'message' => "Dispatched {$concurrency} concurrent orders for product {$productId}",
            'orders_dispatched' => count($dispatched),
            'next_step' => "Wait 3-5s then GET /api/v1/demo/race-result/{$productId}",
            'note' => 'Result endpoint compares completed_orders_for_product to initial_stock to detect oversell',
            'order_refs' => $dispatched,
        ], 202);
    }

    public function raceResult(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $orderCount = Order::whereHas('items', fn($q) => $q->where('product_id', $productId))
            ->where('status', Order::STATUS_COMPLETED)
            ->count();

        $oversold = $product->stock < 0;

        return response()->json([
            'product_id' => $productId,
            'final_stock' => $product->stock,
            'completed_orders' => $orderCount,
            'oversold' => $oversold
        ]);
    }

    public function duplicate(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = $request->integer('product_id');
        $orderRef = Str::uuid()->toString();
        $items = [['product_id' => $productId, 'quantity' => 1]];

        ProcessOrder::dispatch($orderRef, 1, $items);
        ProcessOrder::dispatch($orderRef, 1, $items);

        return response()->json([
            'message' => "Dispatched ProcessOrder twice with same order_ref",
            'order_ref' => $orderRef,
            'next_step' => 'Wait 2s then GET /api/v1/demo/duplicate-result/' . $orderRef,
        ], 202);
    }

    public function duplicateResult(string $orderRef): JsonResponse
    {
        $orders = Order::where('order_ref', $orderRef)->get();
        $expected = 1;
        $actual = $orders->count();

        return response()->json([
            'order_ref' => $orderRef,
            'expected_orders' => $expected,
            'actual_orders' => $actual,
            'idempotent' => $actual === $expected,
            'verdict' => $actual === $expected
                ? 'IDEMPOTENT — duplicate dispatch was a safe no-op'
                : "NOT IDEMPOTENT — {$actual} orders created, expected 1",
            'orders' => $orders,
        ]);
    }

    private function resetForRaceDemo(int $productId, int $stock): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        OrderItem::where('product_id', $productId)->delete();
        Order::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Product::where('id', $productId)->update(['stock' => $stock]);
    }
}
