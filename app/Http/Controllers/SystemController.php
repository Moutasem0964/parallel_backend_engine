<?php

namespace App\Http\Controllers;

use App\Models\JobMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemController extends Controller
{
    public function metrics(): JsonResponse
    {
        $metrics = JobMetric::query()
            ->selectRaw('
            job_class,
            COUNT(*) as samples,
            ROUND(AVG(duration_ms), 2) as avg_ms,
            ROUND(MIN(duration_ms), 2) as min_ms,
            ROUND(MAX(duration_ms), 2) as max_ms,
            ROUND(IFNULL(STDDEV(duration_ms), 0), 2) as stddev_ms,
            CAST(SUM(CASE WHEN succeeded = 1 THEN 1 ELSE 0 END) AS UNSIGNED) as succeeded_count,
            CAST(SUM(CASE WHEN succeeded = 0 THEN 1 ELSE 0 END) AS UNSIGNED) as failed_count
        ')
            ->groupBy('job_class')
            ->get();

        return response()->json([
            'metrics' => $metrics,
        ]);
    }

    public function queue(): JsonResponse
    {
        $queueDepth = Redis::llen('queues:default');
        $delayedDepth = Redis::zcard('queues:default:delayed');
        $reservedDepth = Redis::zcard('queues:default:reserved');
        $failedJobs = DB::table('failed_jobs')->count();

        return response()->json([
            'redis_queue_depth' => $queueDepth,
            'redis_delayed_depth' => $delayedDepth,
            'redis_reserved_depth' => $reservedDepth,
            'failed_jobs_dlq' => $failedJobs,
            'queue_driver' => config('queue.default'),
            'workers_running' => $this->countActiveWorkers(),
        ]);
    }


    private function countActiveWorkers(): int
    {
        $beats = collect(Redis::keys('*horizon*'))->count();
        return $beats > 0 ? $beats : 0;
    }
    public function reset(): JsonResponse
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(403, 'Reset is only allowed in local/testing environments');
        }

        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        \Illuminate\Support\Facades\Redis::flushdb();

        return response()->json([
            'message' => 'reset complete. Database fresh + seeded. Redis flushed.'
        ]);
    }

    public function resetWithBatch(): JsonResponse
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(403, 'Reset is only allowed in local/testing environments');
        }

        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'BatchSeeder',
            '--force' => true,
        ]);

        \Illuminate\Support\Facades\Redis::flushdb();

        return response()->json([
            'message' => 'World reset. 16 products + 500 historical orders for yesterday + Redis flushed.',
            'next_steps' => [
                'Generate yesterday\'s report' => 'POST /api/v1/reports/daily/' . now()->subDay()->toDateString(),
            ],
        ]);
    }
}
