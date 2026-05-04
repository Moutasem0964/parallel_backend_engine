<?php

namespace App\Http\Controllers;

use App\Models\DailySalesReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class ReportController extends Controller
{
    public function generate(string $date): JsonResponse
    {
        Artisan::call('reports:daily', [
            'date' => $date,
            '--chunk' => 100,
        ]);

        return response()->json([
            'message' => "Generation triggered for {$date}",
            'output' => Artisan::output(),
            'next_step' => "GET /api/v1/reports/daily/{$date}",
        ], 202);
    }

    public function show(string $date): JsonResponse
    {
        $report = DailySalesReport::where('report_date', $date)->first();

        if (!$report) {
            return response()->json(['message' => "No report for {$date}"], 404);
        }

        $progress = $report->total_chunks > 0
            ? round(($report->processed_chunks / $report->total_chunks) * 100, 1)
            : 0;

        return response()->json([
            'report_date' => $report->report_date->toDateString(),
            'status' => $report->status,
            'progress' => "{$report->processed_chunks}/{$report->total_chunks} chunks ({$progress}%)",
            'total_orders' => $report->total_orders,
            'total_revenue' => (float) $report->total_revenue,
            'started_at' => $report->started_at,
            'completed_at' => $report->completed_at,
            'failed_records_count' => $report->failedRecords()->count(),
        ]);
    }
}
