<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{orderRef}', [OrderController::class, 'show']);

    Route::post('/demo/race', [DemoController::class, 'race']);
    Route::get('/demo/race-result/{productId}', [DemoController::class, 'raceResult']);
    Route::post('/demo/duplicate', [DemoController::class, 'duplicate']);
    Route::get('/demo/duplicate-result/{orderRef}', [DemoController::class, 'duplicateResult']);

    Route::get('/system/metrics', [SystemController::class, 'metrics']);
    Route::get('/system/queue', [SystemController::class, 'queue']);

    Route::post('/reports/daily/{date}', [ReportController::class, 'generate']);
    Route::get('/reports/daily/{date}', [ReportController::class, 'show']);

    Route::post('/system/reset', [SystemController::class, 'reset']);
    Route::post('/system/reset-with-batch', [SystemController::class, 'resetWithBatch']);
});
