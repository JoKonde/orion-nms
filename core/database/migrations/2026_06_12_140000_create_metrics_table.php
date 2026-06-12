<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table metrics — series temporelles brutes (haute frequence).
     *
     * Volume eleve prevu : index composite pour les requetes par device + periode.
     * Les agregats horaires iront dans metrics_hourly (Job AggregateMetricsJob).
     */
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('metric_type', 50);
            $table->decimal('value', 16, 4);
            // recorded_at = horodatage cote agent (peut differer de created_at serveur).
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['device_id', 'metric_type', 'recorded_at'], 'metrics_device_type_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
