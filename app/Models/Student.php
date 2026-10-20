<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'nis',
        'nisn',
        'gender',
        'name',
        'classroom_id',
        'parent_id',
        'status',
        'tanggal_lahir',
        'asal_siswa',
        'nama_ayah',
        'nama_ibu',
        'nama_wali',
        'no_hp_wali',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function sppInvoices(): HasMany
    {
        return $this->hasMany(SppInvoice::class);
    }

    public function unit(): string
    {
        return $this->classroom?->unit ?: 'sma';
    }

    /**
     * Nomor HP wali kosong atau tidak lolos normalisasi (disimpan null).
     */
    public function scopeWithoutValidWaliPhone(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('no_hp_wali')->orWhere('no_hp_wali', '');
        });
    }
}
