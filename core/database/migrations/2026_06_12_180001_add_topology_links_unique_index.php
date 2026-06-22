<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $exists = collect(DB::select(
            "SHOW INDEX FROM topology_links WHERE Key_name = 'topo_links_pair_type_uniq'"
        ))->isNotEmpty();

        if ($exists) {
            return;
        }

        Schema::table('topology_links', function (Blueprint $table) {
            $table->unique(
                ['source_device_id', 'target_device_id', 'link_type'],
                'topo_links_pair_type_uniq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('topology_links', function (Blueprint $table) {
            $table->dropUnique('topo_links_pair_type_uniq');
        });
    }
};
