<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectScope extends Model
{
    public const UNIT_LPI = 'lpi';

    public const UNIT_SMA = 'sma';

    public const UNIT_SMP = 'smp';

    protected $fillable = ['subject_id', 'unit', 'jenjang'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function label(): string
    {
        $unit = strtoupper($this->unit);

        return $this->jenjang === $unit ? $unit : "{$unit} - {$this->jenjang}";
    }
}
