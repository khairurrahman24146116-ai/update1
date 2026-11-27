<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PPDBWave extends Model
{
    protected $table = 'p_p_d_b_waves';

    protected $fillable = ['name', 'slug', 'description', 'banner_path', 'start_date', 'end_date', 'schedule_days', 'schedule_hours', 'status', 'is_public'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_public' => 'boolean'];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(PPDBRegistration::class, 'wave_id');
    }

    protected static function booted(): void
    {
        static::saving(function (PPDBWave $m) {
            if (empty($m->slug) && ! empty($m->name)) {
                $base = Str::slug($m->name) ?: 'gelombang-'.$m->id;
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->where('id', '!=', $m->id ?? 0)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $m->slug = $slug;
            }
        });
    }
}
