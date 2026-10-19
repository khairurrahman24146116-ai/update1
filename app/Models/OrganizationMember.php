<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationMember extends Model
{
    public const UNIT_LPI = 'lpi';

    public const UNIT_SMA = 'sma';

    public const UNIT_SMP = 'smp';

    /**
     * @var list<string>
     */
    public const UNITS = [self::UNIT_LPI, self::UNIT_SMA, self::UNIT_SMP];

    /**
     * @var array<string, string>
     */
    public const UNIT_LABELS = [
        self::UNIT_LPI => 'LPI Dayah Madani Al-Aziziyah',
        self::UNIT_SMA => 'SMA Madani Al-Aziziyah',
        self::UNIT_SMP => 'SMP Madani Al-Aziziyah',
    ];

    /**
     * Maximum number of levels below the root of a unit (root = level 0).
     */
    public const MAX_DEPTH = 3;

    protected $fillable = [
        'unit',
        'parent_id',
        'name',
        'position',
        'photo_path',
        'sort_order',
        'is_confirmed',
        'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_confirmed' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnit(Builder $query, string $unit): Builder
    {
        return $query->where('unit', $unit);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
