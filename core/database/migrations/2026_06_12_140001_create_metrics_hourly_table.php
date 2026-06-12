<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table metrics_hourly — agregats pre-calcules (moy/min/max par heure).
     *
     * Evite de scanner des millions de lignes brutes pour les graphiques dashboard.
     * Remplie par AggregateMetricsJob (Scheduler horaire).
     */
    public function up(): void
    {
        Schema::create('metrics_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('metric_type', 50);
            $table->decimal('avg_value', 16, 4);
            $table->decimal('min_value', 16, 4);
            $table->decimal('max_value', 16, 4);
            $table->unsignedInteger('sample_count')->default(0);
            $table->timestamp('hour_start');

            $table->unique(['device_id', 'metric_type', 'hour_start'], 'metrics_hourly_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics_hourly');
    }
};
