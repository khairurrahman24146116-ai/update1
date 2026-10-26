<?php

namespace App\Models;

use App\Models\Concerns\ScopedByUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use ScopedByUnit;

    protected $fillable = ['user_id', 'nama', 'nip'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'teacher_unit')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function teacherUnits(): HasMany
    {
        return $this->hasMany(TeacherUnit::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'wali_kelas_id');
    }
}
