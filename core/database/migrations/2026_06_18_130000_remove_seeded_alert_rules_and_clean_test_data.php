<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nettoie les donnees de demo / test (devices + regles d'alerte seed).
     */
    public function up(): void
    {
        // Corrige les anciens types "server" restants en base (enum DeviceType = pc).
        DB::table('devices')
            ->where('type', 'server')
            ->update(['type' => 'pc']);

        $demoIps = ['192.168.1.1', '192.168.1.10', '192.168.1.50'];
        $demoNames = ['Routeur principal', 'Switch bureau', 'Serveur applicatif'];

        DB::table('devices')
            ->whereIn('ip_address', $demoIps)
            ->orWhereIn('name', $demoNames)
            ->orWhere('name', 'like', 'PC-TEST%')
            ->orWhere('name', 'like', 'PC-METRICS%')
            ->delete();

        $seededAlertRules = [
            'CPU eleve',
            'RAM elevee',
            'Disque plein',
            'Temperature critique',
            'Equipement hors ligne',
        ];

        DB::table('alert_rules')
            ->whereIn('name', $seededAlertRules)
            ->delete();
    }

    public function down(): void
    {
        // Pas de restauration des donnees de test supprimees.
    }
};
