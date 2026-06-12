<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table devices — referentiel central de tous les equipements supervises.
     *
     * C'est le coeur du Module 02 : chaque routeur, switch, serveur, etc.
     * sera enregistre ici avant d'etre supervise (ping, SNMP, agent).
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address', 45)->unique(); // IPv4 ou IPv6
            $table->string('mac_address', 17)->nullable();
            $table->string('type');           // DeviceType enum
            $table->string('vendor')->nullable();
            $table->string('model')->nullable();
            $table->string('firmware')->nullable();
            $table->string('status')->default('unknown'); // DeviceStatus enum
            $table->string('discovery_method')->default('manual'); // DiscoveryMethod enum
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // Index pour les filtres frequents du dashboard (par type, par statut).
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
