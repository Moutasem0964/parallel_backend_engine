<?php

namespace App\Jobs;

use App\Models\BatchFailedRecord;
use App\Models\DailySalesReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSalesChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $reportDate,
        public int $chunkNumber,
        public int $totalChunks,
        public array $orderIds
    ) {}

    public function handle(): void
    {
        $chunkRevenue = 0;
        $chunkOrderCount = 0;

        try {
            $results = DB::table('order_items')
                ->whereIn('order_id', $this->orderIds)
                ->select('order_id', DB::raw('SUM(quantity * unit_price) as revenue'))
                ->groupBy('order_id')
                ->get();

            $chunkOrderCount = $results->count();
            $chunkRevenue = (float) $results->sum('revenue');
        } catch (\Throwable $e) {
            $this->recordFailedRecords($e->getMessage());
            Log::channel('batch')->warning('chunk failed mid-aggregation', [
                'chunk' => $this->chunkNumber,
                'error' => $e->getMessage(),
            ]);
        }

        DB::table('daily_sales_reports')
            ->where('report_date', $this->reportDate)
            ->update([
                'total_orders' => DB::raw("total_orders + {$chunkOrderCount}"),
                'total_revenue' => DB::raw("total_revenue + {$chunkRevenue}"),
                'processed_chunks' => DB::raw('processed_chunks + 1'),
            ]);

        $this->finalizeIfDone();

        Log::channel('batch')->info('chunk completed', [
            'chunk' => "{$this->chunkNumber}/{$this->totalChunks}",
            'orders' => $chunkOrderCount,
            'revenue' => $chunkRevenue,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->recordFailedRecords('Chunk-level failure: ' . $exception->getMessage());

        DB::table('daily_sales_reports')
            ->where('report_date', $this->reportDate)
            ->increment('processed_chunks');

        $this->finalizeIfDone();
    }

    private function recordFailedRecords(string $error): void
    {
        $rows = array_map(fn($id) => [
            'report_date' => $this->reportDate,
            'order_id' => $id,
            'chunk_number' => $this->chunkNumber,
            'error_message' => $error,
            'created_at' => now(),
        ], $this->orderIds);

        BatchFailedRecord::insert($rows);
    }

    private function finalizeIfDone(): void
    {
        $report = DailySalesReport::where('report_date', $this->reportDate)->first();
        if (!$report || $report->processed_chunks < $this->totalChunks) {
            return;
        }

        $hasFailures = BatchFailedRecord::where('report_date', $this->reportDate)->exists();

        $report->update([
            'status' => $hasFailures
                ? DailySalesReport::STATUS_COMPLETED_WITH_ERRORS
                : DailySalesReport::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
