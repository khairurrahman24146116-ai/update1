<?php

namespace App\Observers;

use App\Models\StudentEnrollment;

class StudentEnrollmentObserver
{
    public function created(StudentEnrollment $enrollment): void
    {
        $this->log($enrollment);
    }

    public function updated(StudentEnrollment $enrollment): void
    {
        if ($enrollment->wasChanged(['sumber', 'status', 'class_id', 'unit_id', 'jenjang'])) {
            $this->log($enrollment);
        }
    }

    private function log(StudentEnrollment $enrollment): void
    {
        $action = match ($enrollment->sumber) {
            StudentEnrollment::SUMBER_NAIK_JENJANG => 'student.enrollment.naik_jenjang',
            StudentEnrollment::SUMBER_MANUAL => 'student.enrollment.manual',
            default => null,
        };

        if ($action === null) {
            return;
        }

        activity_log($action, $enrollment->dibuat_oleh, StudentEnrollment::class, $enrollment->id, [
            'student_id' => $enrollment->student_id,
            'unit_id' => $enrollment->unit_id,
            'jenjang' => $enrollment->jenjang,
            'class_id' => $enrollment->class_id,
            'status' => $enrollment->status,
            'sumber' => $enrollment->sumber,
            'alasan_manual' => $enrollment->alasan_manual,
        ]);
    }
}
