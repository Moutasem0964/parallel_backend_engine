<?php

namespace Database\Seeders;

use App\Models\BatchFailedRecord;
use App\Models\JobMetric;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->cleanSlate();

        User::factory()->create([
            'name' => 'Demo Buyer',
            'email' => 'demo@local.test',
        ]);

        Product::factory()->count(4)->create();
        Product::factory()->lowStock(5)->count(2)->create();
        Product::factory()->lastItem()->count(2)->create();
        Product::factory()->bulk()->count(8)->create();

        $count = Product::count();
        $this->command->info("Seeded: 1 demo user + {$count} products (4 standard, 2 low-stock, 2 last-item, 8 bulk).");
    }

    private function cleanSlate(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        BatchFailedRecord::query()->truncate();
        JobMetric::query()->truncate();
        OrderItem::query()->truncate();
        Order::query()->truncate();
        Product::query()->truncate();
        User::query()->where('email', 'like', '%@local.test')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
