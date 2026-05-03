<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMetric extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'job_class',
        'duration_ms',
        'succeeded',
        'attempt',
        'created_at',
    ];

    protected $casts = [
        'duration_ms' => 'decimal:2',
        'succeeded' => 'boolean',
        'attempt' => 'integer',
        'created_at' => 'datetime',
    ];
}