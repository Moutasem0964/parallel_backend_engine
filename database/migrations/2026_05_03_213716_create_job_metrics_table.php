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
        Schema::create('job_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('job_class', 191);
            $table->decimal('duration_ms', 10, 2);
            $table->boolean('succeeded');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index('job_class');
            $table->index(['job_class', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_metrics');
    }
};
