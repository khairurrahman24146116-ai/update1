<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['nis', 'nisn', 'gender', 'name', 'classroom_id', 'parent_id', 'status'];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function sppInvoices(): HasMany
    {
        return $this->hasMany(SppInvoice::class);
    }

    public function unit(): string
    {
        return $this->classroom?->unit ?: 'sma';
    }
}
