<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $date = Carbon::yesterday()->toDateString();

        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->error('Run DemoSeeder first — products must exist.');
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'batch@local.test'],
            ['name' => 'Batch User', 'password' => bcrypt('password')]
        );

        $orderCount = 500;
        $this->command->info("Seeding {$orderCount} orders for {$date}...");

        for ($i = 0; $i < $orderCount; $i++) {
            $order = Order::factory()
                ->forDate($date)
                ->completed()
                ->create(['user_id' => $user->id]);

            $itemCount = fake()->numberBetween(1, 3);
            $total = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $qty = fake()->numberBetween(1, 3);

                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                ]);

                $total += $product->price * $qty;
            }

            $order->update(['total_price' => $total]);
        }

        $this->command->info("Done. {$orderCount} orders created for {$date}.");
    }
}
