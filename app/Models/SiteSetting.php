<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'app_name', 'nspp', 'npsn', 'jenjang', 'akreditasi', 'milad', 'tagline', 'vision',
        'address', 'logo_path', 'favicon', 'kop_surat', 'phone', 'pstn', 'wa_helpdesk', 'email',
        'map_lat', 'map_lng', 'instagram', 'youtube', 'tiktok', 'facebook', 'hero_image',
        'homepage_badge_text', 'homepage_title', 'homepage_tagline', 'homepage_logo_path',
        'prayer_city_name', 'prayer_latitude', 'prayer_longitude', 'prayer_timezone', 'prayer_card_visible',
        'hero_overlay_opacity',
    ];

    protected $casts = [
        'social_enabled' => 'array',
        'prayer_card_visible' => 'boolean',
        'prayer_latitude' => 'decimal:7',
        'prayer_longitude' => 'decimal:7',
        'hero_overlay_opacity' => 'integer',
    ];
}
