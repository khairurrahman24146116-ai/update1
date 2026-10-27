<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regresi untuk temuan #1 + #2 dari audit:
     * 1. POST /api/schedules/{id}/attendances 403 untuk semua role,
     *    karena authorize('store', Schedule) meresolusi SchedulePolicy
     *    yang tidak punya method store → fitur simpan absensi mati.
     * 2. GET /api/schedules/{id}/sheet dan /attendances lolos tanpa syarat
     *    (SchedulePolicy::view = true) → wali_murid/bendahara bisa membaca
     *    lembar absensi seluruh kelas (IDOR).
     */
    private User $admin;

    private User $guru;

    private User $guruLain;

    private User $wali;

    private User $bend;

    private Classroom $classroom;

    private Subject $subject;

    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin_utama']);
        $this->guru = User::factory()->create(['role' => 'guru']);
        $this->guruLain = User::factory()->create(['role' => 'guru']);
        $this->wali = User::factory()->create(['role' => 'wali_murid']);
        $this->bend = User::factory()->create(['role' => 'bendahara']);

        $this->classroom = Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        $this->subject = Subject::create(['name' => 'Fisika', 'code' => 'FIS']);
        $this->schedule = Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->guru->id,
            'day_of_week' => 'mon',
            'start_time' => '07:00',
            'end_time' => '08:30',
        ]);
    }

    private function student(string $suffix): Student
    {
        return Student::create([
            'nis' => 'NIS'.$suffix,
            'nisn' => '0070900'.$suffix,
            'name' => 'Siswa '.$suffix,
            'gender' => 'L',
            'classroom_id' => $this->classroom->id,
            'status' => 'aktif',
        ]);
    }

    // ---- Temuan #1: store absensi harus hidup dan terbatas ----

    public function test_guru_pemilik_jadwal_bisa_simpan_absensi(): void
    {
        $s1 = $this->student('101');
        $s2 = $this->student('102');

        $this->actingAs($this->guru)
            ->postJson("/api/schedules/{$this->schedule->id}/attendances?date=2026-10-01", ['rows' => [
                ['student_id' => $s1->id, 'status' => 'hadir'],
                ['student_id' => $s2->id, 'status' => 'izin', 'notes' => 'surat dokter'],
            ]])
            ->assertNoContent();

        $this->assertTrue(
            Attendance::where('student_id', $s1->id)->whereDate('date', '2026-10-01')->where('status', 'hadir')->exists(),
            'Absensi siswa 1 seharusnya tersimpan (hadir)'
        );
        $this->assertTrue(
            Attendance::where('student_id', $s2->id)->whereDate('date', '2026-10-01')->where('status', 'izin')->where('notes', 'surat dokter')->exists(),
            'Absensi siswa 2 seharusnya tersimpan (izin + catatan)'
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'attendance.store',
            'user_id' => $this->guru->id,
        ]);
    }

    /**
     * AttendancePolicy::store hanya mengizinkan guru pemilik jadwal;
     * admin boleh baca (view) tapi tidak menulis absensi.
     */
    public function test_admin_tidak_bisa_simpan_absensi_hanya_baca(): void
    {
        $s1 = $this->student('103');

        $this->actingAs($this->admin)
            ->postJson("/api/schedules/{$this->schedule->id}/attendances?date=2026-10-01", ['rows' => [
                ['student_id' => $s1->id, 'status' => 'hadir'],
            ]])
            ->assertForbidden();

        $this->assertFalse(
            Attendance::where('student_id', $s1->id)->whereDate('date', '2026-10-01')->exists(),
            'Admin hanya boleh membaca, bukan menulis absensi'
        );
    }

    public function test_saving_attendance_twice_updates_without_duplicates(): void
    {
        $s1 = $this->student('106');
        $s2 = $this->student('107');

        $this->actingAs($this->guru)
            ->postJson("/api/schedules/{$this->schedule->id}/attendances?date=2026-10-02", ['rows' => [
                ['student_id' => $s1->id, 'status' => 'hadir'],
                ['student_id' => $s2->id, 'status' => 'hadir'],
            ]])
            ->assertNoContent();

        $this->actingAs($this->guru)
            ->postJson("/api/schedules/{$this->schedule->id}/attendances?date=2026-10-02", ['rows' => [
                ['student_id' => $s1->id, 'status' => 'sakit', 'notes' => 'demam'],
                ['student_id' => $s2->id, 'status' => 'alfa'],
            ]])
            ->assertNoContent();

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $s1->id,
            'status' => 'sakit',
            'notes' => 'demam',
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $s2->id,
            'status' => 'alfa',
        ]);
    }

    public function test_guru_bukan_pemilik_tidak_bisa_simpan_absensi(): void
    {
        $s1 = $this->student('104');

        $this->actingAs($this->guruLain)
            ->postJson("/api/schedules/{$this->schedule->id}/attendances?date=2026-10-01", ['rows' => [
                ['student_id' => $s1->id, 'status' => 'hadir'],
            ]])
            ->assertForbidden();

        $this->assertFalse(
            Attendance::where('student_id', $s1->id)->whereDate('date', '2026-10-01')->exists(),
            'Tidak seharusnya ada baris absensi yang tersimpan untuk guru non-pemilik'
        );
    }

    public function test_wali_dan_bendahara_tidak_bisa_simpan_absensi(): void
    {
        $s1 = $this->student('105');

        foreach ([$this->wali, $this->bend] as $user) {
            $this->actingAs($user)
                ->postJson("/api/schedules/{$this->schedule->id}/attendances?date=2026-10-01", ['rows' => [
                    ['student_id' => $s1->id, 'status' => 'hadir'],
                ]])
                ->assertForbidden();
        }

        $this->assertFalse(
            Attendance::where('student_id', $s1->id)->whereDate('date', '2026-10-01')->exists(),
            'Tidak seharusnya ada baris absensi yang tersimpan untuk wali/bendahara'
        );
    }

    // ---- Temuan #2: baca lembar absensi jangan bocor ----

    public function test_guru_pemilik_dan_admin_bisa_baca_sheet_absensi(): void
    {
        $this->actingAs($this->guru)->getJson("/api/schedules/{$this->schedule->id}/sheet")->assertOk();
        $this->actingAs($this->admin)->getJson("/api/schedules/{$this->schedule->id}/sheet")->assertOk();
    }

    public function test_wali_dan_bendahara_ditolak_dari_sheet_dan_index_absensi(): void
    {
        foreach ([$this->wali, $this->bend] as $user) {
            $this->actingAs($user)->getJson("/api/schedules/{$this->schedule->id}/sheet")->assertForbidden();
            $this->actingAs($user)->getJson("/api/schedules/{$this->schedule->id}/attendances")->assertForbidden();
        }
    }

    public function test_guru_bukan_pemilik_ditolak_dari_sheet_absensi(): void
    {
        $this->actingAs($this->guruLain)->getJson("/api/schedules/{$this->schedule->id}/sheet")->assertForbidden();
        $this->actingAs($this->guruLain)->getJson("/api/schedules/{$this->schedule->id}/attendances")->assertForbidden();
    }
}
