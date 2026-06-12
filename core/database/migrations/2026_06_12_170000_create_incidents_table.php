<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            // open | assigned | in_progress | resolved | closed
            $table->string('status', 20)->default('open');
            // low | medium | high | critical
            $table->string('priority', 16);
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            // Alerte source (escalade auto ou manuelle)
            $table->foreignId('alert_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->unique('alert_id');
            $table->index(['status', 'priority']);
            $table->index('assigned_to');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
