<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('homepage_badge_text')->nullable()->after('hero_image');
            $table->string('homepage_title')->nullable()->after('homepage_badge_text');
            $table->string('homepage_tagline')->nullable()->after('homepage_title');
            $table->string('prayer_city_name')->default('Banda Aceh')->after('homepage_tagline');
            $table->decimal('prayer_latitude', 10, 7)->default(5.5483000)->after('prayer_city_name');
            $table->decimal('prayer_longitude', 10, 7)->default(95.3238000)->after('prayer_latitude');
            $table->string('prayer_timezone')->default('Asia/Jakarta')->after('prayer_longitude');
            $table->boolean('prayer_card_visible')->default(true)->after('prayer_timezone');
            $table->unsignedTinyInteger('hero_overlay_opacity')->default(40)->after('prayer_card_visible');
            $table->string('homepage_logo_path')->nullable()->after('hero_overlay_opacity');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'homepage_badge_text',
                'homepage_title',
                'homepage_tagline',
                'prayer_city_name',
                'prayer_latitude',
                'prayer_longitude',
                'prayer_timezone',
                'prayer_card_visible',
                'hero_overlay_opacity',
                'homepage_logo_path',
            ]);
        });
    }
};
