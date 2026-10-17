<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroMedia extends Model
{
    protected $table = 'hero_media';

    protected $fillable = [
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'sort_order',
        'is_active',
        'poster_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'file_size' => 'integer',
    ];
}
