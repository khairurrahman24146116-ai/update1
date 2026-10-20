<?php

namespace App\Models;

use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    public const STATUS_PREVIEW = 'preview';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'file_name',
        'diimport_oleh',
        'tahun_ajaran_id',
        'total_baris',
        'berhasil',
        'gagal',
        'peringatan',
        'update_existing',
        'status',
        'hasil',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_baris' => 'integer',
            'berhasil' => 'integer',
            'gagal' => 'integer',
            'peringatan' => 'integer',
            'update_existing' => 'boolean',
            'hasil' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'batch_id');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diimport_oleh');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'tahun_ajaran_id');
    }

    public function isPreview(): bool
    {
        return $this->status === self::STATUS_PREVIEW;
    }
}
