<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('topology_links')) {
            return;
        }

        Schema::create('topology_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('target_device_id')->constrained('devices')->cascadeOnDelete();
            // lldp | nmap_subnet | manual
            $table->string('link_type', 32);
            // up | down | unknown
            $table->string('link_status', 16)->default('unknown');
            $table->string('source_interface')->nullable();
            $table->string('target_interface')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_device_id', 'target_device_id', 'link_type'],
                'topo_links_pair_type_uniq'
            );
            $table->index('link_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topology_links');
    }
};
