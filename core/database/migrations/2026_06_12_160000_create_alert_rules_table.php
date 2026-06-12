<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // metric_threshold (cpu>90%) ou device_offline
            $table->string('rule_type', 32);
            $table->string('metric_type', 32)->nullable();
            // Operateur de comparaison : gt, gte, lt, lte
            $table->string('operator', 8)->nullable();
            $table->decimal('threshold', 12, 4)->nullable();
            $table->string('severity', 16);
            // null = regle globale pour tous les devices
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_enabled')->default(true);
            // Delai minimum entre deux alertes identiques (evite le spam)
            $table->unsignedSmallInteger('cooldown_minutes')->default(15);
            $table->timestamps();

            $table->index(['rule_type', 'is_enabled']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
