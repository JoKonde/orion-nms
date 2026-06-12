<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('severity', 16);
            // raised | acknowledged | resolved
            $table->string('status', 16)->default('raised');
            $table->string('title');
            $table->text('message');
            $table->string('metric_type', 32)->nullable();
            $table->decimal('metric_value', 12, 4)->nullable();
            $table->timestamp('raised_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['device_id', 'status']);
            $table->index('raised_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
