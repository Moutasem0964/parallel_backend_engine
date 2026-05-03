<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySalesReport extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'report_date',
        'status',
        'total_chunks',
        'processed_chunks',
        'total_orders',
        'total_revenue',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_chunks' => 'integer',
        'processed_chunks' => 'integer',
        'total_orders' => 'integer',
        'total_revenue' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function failedRecords(): HasMany
    {
        return $this->hasMany(BatchFailedRecord::class, 'report_date', 'report_date');
    }
}