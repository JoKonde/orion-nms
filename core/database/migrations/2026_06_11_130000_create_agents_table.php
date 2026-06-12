<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            // Chaque agent est lie a un device de type "server" (1 agent = 1 machine).
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->uuid('agent_uuid')->unique();
            $table->string('hostname');
            $table->string('os');               // windows, linux
            $table->string('os_version')->nullable();
            $table->string('architecture')->nullable(); // x64, arm64...
            $table->string('agent_version')->nullable();
            // Hash bcrypt de la cle API (jamais la cle en clair en base).
            $table->string('api_key_hash');
            $table->string('status')->default('offline');
            $table->timestamp('registered_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
