<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SppInvoice extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'unit',
        'student_id',
        'period',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SppPayment::class);
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }

    public function refreshStatus(): void
    {
        $paid = round((float) $this->payments()->sum('amount'), 2);
        $amount = round((float) $this->amount, 2);

        $status = self::STATUS_UNPAID;
        if ($paid > 0 && $paid < $amount) {
            $status = self::STATUS_PARTIAL;
        } elseif ($paid >= $amount && $amount > 0) {
            $status = self::STATUS_PAID;
        }

        $this->forceFill(['paid_amount' => $paid, 'status' => $status])->save();
    }
}
