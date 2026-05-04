<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSalesChunk;
use App\Models\DailySalesReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDailySalesReport extends Command
{
    protected $signature = 'reports:daily {date} {--chunk=100} {--force}';
    protected $description = 'Generate daily sales report by streaming orders in chunks';

    public function handle(): int
    {
        $date = $this->argument('date');
        $chunkSize = (int) $this->option('chunk');

        $existing = DailySalesReport::where('report_date', $date)->first();

        if ($existing && $existing->status === DailySalesReport::STATUS_COMPLETED) {
            if (!$this->option('force')) {
                $this->info("Report for {$date} already complete. Use --force to regenerate.");
                return self::SUCCESS;
            }

            DB::table('daily_sales_reports')->where('report_date', $date)->delete();
            DB::table('batch_failed_records')->where('report_date', $date)->delete();
        }

        $totalRecords = DB::table('orders')->whereDate('created_at', $date)->count();

        if ($totalRecords === 0) {
            $this->info("No orders for {$date}.");
            return self::SUCCESS;
        }

        $totalChunks = (int) ceil($totalRecords / $chunkSize);

        DailySalesReport::updateOrCreate(
            ['report_date' => $date],
            [
                'status' => DailySalesReport::STATUS_PROCESSING,
                'total_chunks' => $totalChunks,
                'processed_chunks' => 0,
                'total_orders' => 0,
                'total_revenue' => 0,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $chunkNumber = 0;
        DB::table('orders')
            ->whereDate('created_at', $date)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($orders) use ($date, &$chunkNumber, $totalChunks) {
                $chunkNumber++;
                ProcessSalesChunk::dispatch(
                    $date,
                    $chunkNumber,
                    $totalChunks,
                    $orders->pluck('id')->toArray()
                );
            });

        $this->info("Dispatched {$totalChunks} chunks for {$date} ({$totalRecords} orders).");
        return self::SUCCESS;
    }
}
