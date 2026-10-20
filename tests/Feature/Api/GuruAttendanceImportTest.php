<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class GuruAttendanceImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeExcel(array $rows): UploadedFile
    {
        $ss = new Spreadsheet;
        $sh = $ss->getActiveSheet();
        $sh->setCellValue('A1', 'NIS');
        $sh->setCellValue('B1', 'NISN');
        $sh->setCellValue('C1', 'Status');
        $sh->setCellValue('D1', 'Keterangan');
        $r = 2;
        foreach ($rows as $row) {
            $sh->setCellValue("A{$r}", $row[0]);
            $sh->setCellValue("B{$r}", $row[1]);
            $sh->setCellValue("C{$r}", $row[2]);
            $sh->setCellValue("D{$r}", $row[3] ?? '');
            $r++;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'tst');
        (new Xlsx($ss))->save($tmp);

        return new UploadedFile($tmp, 'abs.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function seedSchedule(User $guru): Schedule
    {
        $cl = Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        $sub = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);

        return Schedule::create(['classroom_id' => $cl->id, 'subject_id' => $sub->id, 'teacher_id' => $guru->id, 'day_of_week' => 'mon', 'start_time' => '08:00', 'end_time' => '09:00', 'academic_year' => '2026/2027']);
    }

    public function test_guru_preview_and_import(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru);
        $stu = Student::create(['nis' => '2026001', 'nisn' => '0071234567', 'name' => 'Andi', 'gender' => 'L', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $file = $this->makeExcel([['2026001', '', 'hadir', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk()->assertJsonPath('valid', 1);
        $this->assertDatabaseCount('attendances', 0);
        $file2 = $this->makeExcel([['2026001', '', 'hadir', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import', ['file' => $file2, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk()->assertJsonPath('imported', 1);
        $this->assertEquals(1, Attendance::where('student_id', $stu->id)->where('schedule_id', $sch->id)->whereDate('date', '2026-09-18')->where('status', 'hadir')->count());
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('activity_logs', ['action' => 'attendance.import.preview']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'attendance.import', 'user_id' => $guru->id]);
    }

    public function test_preview_does_not_write(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru);
        Student::create(['nis' => '2026002', 'nisn' => '0071234568', 'name' => 'Budi', 'gender' => 'L', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $file = $this->makeExcel([['2026002', '', 'izin', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_unauthorized_guru_cannot_import_other_schedule(): void
    {
        $guru1 = User::factory()->create(['role' => 'guru']);
        $guru2 = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru1);
        Student::create(['nis' => '2026003', 'nisn' => '0071234569', 'name' => 'Cici', 'gender' => 'P', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $file = $this->makeExcel([['2026003', '', 'hadir', '']]);
        $this->actingAs($guru2)->postJson('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertStatus(403);
        $this->actingAs($guru2)->post('/api/guru/attendance/import', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertStatus(403);
    }

    public function test_wali_cannot_import(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $wali = User::factory()->create(['role' => 'wali_murid']);
        $sch = $this->seedSchedule($guru);
        Student::create(['nis' => '2026004', 'nisn' => '0071234570', 'name' => 'Dedi', 'gender' => 'L', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $file = $this->makeExcel([['2026004', '', 'hadir', '']]);
        $this->actingAs($wali)->post('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertStatus(403);
    }

    public function test_bendahara_cannot_import(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $bend = User::factory()->create(['role' => 'bendahara']);
        $sch = $this->seedSchedule($guru);
        Student::create(['nis' => '2026005', 'nisn' => '0071234571', 'name' => 'Eka', 'gender' => 'P', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $file = $this->makeExcel([['2026005', '', 'hadir', '']]);
        $this->actingAs($bend)->post('/api/guru/attendance/import', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertStatus(403);
    }

    public function test_invalid_status_rejected(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru);
        Student::create(['nis' => '2026006', 'nisn' => '0071234572', 'name' => 'Fani', 'gender' => 'P', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $file = $this->makeExcel([['2026006', '', 'ngantuk', '']]);
        $res = $this->actingAs($guru)->post('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk();
        $this->assertEquals(1, $res->json('invalid'));
        $file2 = $this->makeExcel([['2026006', '', 'ngantuk', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import', ['file' => $file2, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk()->assertJsonPath('imported', 0);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_invalid_student_rejected(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru);
        $file = $this->makeExcel([['9999999', '', 'hadir', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk()->assertJsonPath('invalid', 1);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_attendance_skipped(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru);
        $stu = Student::create(['nis' => '2026007', 'nisn' => '0071234573', 'name' => 'Gina', 'gender' => 'P', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        Attendance::create(['schedule_id' => $sch->id, 'student_id' => $stu->id, 'date' => '2026-09-18', 'status' => 'hadir']);
        $file = $this->makeExcel([['2026007', '', 'hadir', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import/preview', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk()->assertJsonPath('invalid', 1);
        $file2 = $this->makeExcel([['2026007', '', 'hadir', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import', ['file' => $file2, 'schedule_id' => $sch->id, 'date' => '2026-09-18'])->assertOk()->assertJsonPath('failed', 1);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_no_student_created_on_attendance_import(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $sch = $this->seedSchedule($guru);
        Student::create(['nis' => '2026008', 'nisn' => '0071234574', 'name' => 'Hani', 'gender' => 'P', 'classroom_id' => $sch->classroom_id, 'status' => 'aktif']);
        $before = Student::count();
        $file = $this->makeExcel([['9999999', '', 'hadir', '']]);
        $this->actingAs($guru)->post('/api/guru/attendance/import', ['file' => $file, 'schedule_id' => $sch->id, 'date' => '2026-09-18']);
        $this->assertEquals($before, Student::count());
    }
}
