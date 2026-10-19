<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['unit', 'type', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function transactions(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'category_id');
    }
}
