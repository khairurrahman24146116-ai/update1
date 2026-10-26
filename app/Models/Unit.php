<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['kode', 'nama'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_unit')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function userUnits(): HasMany
    {
        return $this->hasMany(UserUnit::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_unit')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function teacherUnits(): HasMany
    {
        return $this->hasMany(TeacherUnit::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
