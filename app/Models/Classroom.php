<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = ['name', 'grade', 'academic_year', 'unit'];

    protected function casts(): array
    {
        return ['grade' => 'integer'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }
}
