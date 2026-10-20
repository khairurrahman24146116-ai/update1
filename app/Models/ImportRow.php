<?php

namespace App\Models;

use Database\Factories\ImportRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    /** @use HasFactory<ImportRowFactory> */
    use HasFactory;

    public const STATUS_VALID = 'valid';

    public const STATUS_WARNING = 'peringatan';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'batch_id',
        'nomor_baris',
        'data_mentah',
        'data_valid',
        'status',
        'pesan',
        'aksi',
        'student_id',
    ];

    protected function casts(): array
    {
        return [
            'nomor_baris' => 'integer',
            'data_mentah' => 'array',
            'data_valid' => 'array',
            'pesan' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
