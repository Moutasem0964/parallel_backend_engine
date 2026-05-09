<?php

namespace App\Jobs;

use App\Models\JobMetric;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DeadlockException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public string $orderRef,
        public int $userId,
        public array $items
    ) {}

    public function handle(): void
    {
        $start = microtime(true);
        $succeeded = false;

        if (Order::where('order_ref', $this->orderRef)->exists()) {
            Log::channel('concurrency')->info('idempotent skip', ['order_ref' => $this->orderRef]);
            $this->recordMetric($start, true);
            return;
        }

        try {
            DB::transaction(function () {
                $order = Order::create([
                    'order_ref' => $this->orderRef,
                    'user_id' => $this->userId,
                    'status' => Order::STATUS_PROCESSING,
                    'total_price' => 0,
                ]);

                $items = $this->items;
                usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

                $total = 0;
                foreach ($items as $item) {
                    $product = Product::where('id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                     //usleep(1000000);

                    if (!$product || $product->stock < $item['quantity']) {
                        throw new \RuntimeException(
                            "Insufficient stock for product {$item['product_id']}"
                        );
                    }

                    $product->decrement('stock', $item['quantity']);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                    ]);

                    $total += $product->price * $item['quantity'];
                }

                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                    'total_price' => $total,
                ]);
            });

            $succeeded = true;
        } catch (DeadlockException $e) {
            Log::channel('concurrency')->warning('deadlock — retrying', [
                'order_ref' => $this->orderRef,
                'attempt' => $this->attempts(),
            ]);
            $this->recordMetric($start, false);
            throw $e;
        } catch (\Throwable $e) {
            Log::channel('concurrency')->warning('order failed permanently', [
                'order_ref' => $this->orderRef,
                'error' => $e->getMessage(),
            ]);
            $this->recordMetric($start, false);
            $this->fail($e);
            return;
        }

        $this->recordMetric($start, $succeeded);
    }

    private function recordMetric(float $start, bool $succeeded): void
    {
        JobMetric::create([
            'job_class' => self::class,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            'succeeded' => $succeeded,
            'attempt' => $this->attempts(),
            'created_at' => now(),
        ]);
    }
}
