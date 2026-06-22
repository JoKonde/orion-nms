<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renomme le type "server" en "pc" et supprime les equipements de demo / test.
     *
     * Les equipements du parc seront desormais crees via le scan reseau (Nmap)
     * ou l'enregistrement manuel / agent.
     */
    public function up(): void
    {
        DB::table('devices')
            ->where('type', 'server')
            ->update(['type' => 'pc']);

        $demoIps = ['192.168.1.1', '192.168.1.10', '192.168.1.50'];

        DB::table('devices')
            ->whereIn('ip_address', $demoIps)
            ->delete();

        DB::table('devices')
            ->where('name', 'like', 'PC-TEST%')
            ->orWhere('name', 'like', 'PC-METRICS%')
            ->delete();
    }

    public function down(): void
    {
        DB::table('devices')
            ->where('type', 'pc')
            ->update(['type' => 'server']);
    }
};
