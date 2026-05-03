<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batch_failed_records', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_number');
            $table->text('error_message');
            $table->timestamp('created_at')->useCurrent();

            $table->index('report_date');
            $table->index(['report_date', 'chunk_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_failed_records');
    }
};
