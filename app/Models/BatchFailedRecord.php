<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchFailedRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'report_date',
        'order_id',
        'chunk_number',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'chunk_number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}