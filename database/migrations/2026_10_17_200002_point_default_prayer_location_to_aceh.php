<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repoint the neutral prayer-location placeholder to Aceh (WIB).
     * Only touches rows that still hold the original placeholder value,
     * so an admin-configured location is never overwritten.
     */
    public function up(): void
    {
        DB::table('site_settings')
            ->where('prayer_city_name', 'Pusat Indonesia')
            ->update([
                'prayer_city_name' => 'Banda Aceh',
                'prayer_latitude' => 5.5483000,
                'prayer_longitude' => 95.3238000,
                'prayer_timezone' => 'Asia/Jakarta',
            ]);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('prayer_city_name', 'Banda Aceh')
            ->where('prayer_latitude', 5.5483000)
            ->where('prayer_longitude', 95.3238000)
            ->update([
                'prayer_city_name' => 'Pusat Indonesia',
                'prayer_latitude' => -2.5489000,
                'prayer_longitude' => 118.0149000,
            ]);
    }
};
