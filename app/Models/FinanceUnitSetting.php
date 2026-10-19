<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceUnitSetting extends Model
{
    use HasFactory;

    protected $fillable = ['unit', 'opening_balance'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2'];
    }
}
