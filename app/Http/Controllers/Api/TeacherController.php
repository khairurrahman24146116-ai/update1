<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SiteSetting;
use App\Models\Student;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isWaliMurid()) {
            return User::query()
                ->where('role', User::ROLE_GURU)
                ->select('id', 'name', 'email')
                ->get();
        }

        return $this->forWaliMurid($user);
    }

    /**
     * Wali hanya menerima guru yang mengajar di kelas anaknya, tanpa data
     * pribadi guru. Kontak yang ditampilkan adalah kontak sekolah.
     */
    private function forWaliMurid(User $user): array
    {
        $classroomIds = Student::query()
            ->where('parent_id', $user->id)
            ->whereNotNull('classroom_id')
            ->pluck('classroom_id')
            ->unique()
            ->values();

        if ($classroomIds->isEmpty()) {
            return [];
        }

        $rows = TeacherSubject::query()
            ->whereIn('classroom_id', $classroomIds)
            ->with(['teacher:id,name', 'subject:id,name'])
            ->get()
            ->merge(
                Schedule::query()
                    ->whereIn('classroom_id', $classroomIds)
                    ->with(['teacher:id,name', 'subject:id,name'])
                    ->get()
            );

        $settings = SiteSetting::query()->first(['phone', 'wa_helpdesk', 'email']);
        $schoolContact = $settings?->phone ?: $settings?->wa_helpdesk ?: $settings?->email;

        $teachers = [];

        foreach ($rows as $row) {
            if ($row->teacher === null) {
                continue;
            }

            $id = (int) $row->teacher->id;

            $teachers[$id] ??= [
                'name' => $row->teacher->name,
                'subjects' => [],
            ];

            if ($row->subject?->name) {
                $teachers[$id]['subjects'][] = $row->subject->name;
            }
        }

        return collect($teachers)
            ->sortBy('name')
            ->values()
            ->map(fn (array $teacher): array => [
                'name' => $teacher['name'],
                'subjects' => array_values(array_unique($teacher['subjects'])),
                'school_contact' => $schoolContact,
            ])
            ->all();
    }
}
