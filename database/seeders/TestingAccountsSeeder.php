<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\FinanceUnitSetting;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $roles = ['admin_utama', 'operator', 'pending', 'guru', 'bendahara_lpi', 'bendahara_sma', 'wali_murid'];

        foreach ($roles as $role) {
            User::updateOrCreate(
                ['email' => "{$role}@madani.test"],
                [
                    'name' => ucfirst($role).' Test Account',
                    'password' => $password,
                    'role' => $role,
                    'unit' => match ($role) {
                        'bendahara_lpi' => 'lpi',
                        'bendahara_sma' => 'sma',
                        default => null,
                    },
                    'is_active' => true,
                    'is_protected' => $role === 'admin_utama',
                ]
            );
        }
        $guru = User::where('email', 'guru@madani.test')->first();
        $kelas = Classroom::where('unit', 'sma')->first() ?? Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027', 'unit' => 'sma']);
        $kelasLpi = Classroom::where('unit', 'lpi')->first() ?? Classroom::create(['name' => 'LPI-1', 'grade' => 7, 'academic_year' => '2026/2027', 'unit' => 'lpi']);
        $fisika = Subject::where('code', 'FIS')->first() ?? Subject::firstOrCreate(['code' => 'FIS'], ['name' => 'Fisika']);
        if ($guru && $kelas && $fisika) {
            TeacherSubject::firstOrCreate(['teacher_id' => $guru->id, 'classroom_id' => $kelas->id, 'subject_id' => $fisika->id]);
            Schedule::firstOrCreate(
                ['teacher_id' => $guru->id, 'classroom_id' => $kelas->id, 'subject_id' => $fisika->id, 'day_of_week' => 'mon'],
                ['start_time' => '08:00', 'end_time' => '09:30']
            );
            $nisnMap = ['Andi' => '0071234567', 'Budi' => '0071234568', 'Citra' => '0071234569'];
            foreach (['Andi', 'Budi', 'Citra'] as $nm) {
                $s = Student::where('name', $nm)->where('classroom_id', $kelas->id)->first();
                if (! $s) {
                    Student::create(['name' => $nm, 'nis' => 'NIS'.rand(1000, 9999), 'nisn' => $nisnMap[$nm], 'gender' => 'L', 'classroom_id' => $kelas->id, 'status' => 'aktif']);
                } elseif (! $s->nisn) {
                    $s->update(['nisn' => $nisnMap[$nm]]);
                }
            }
            $wali = User::where('email', 'wali_murid@madani.test')->first();
            if ($wali) {
                $andi = Student::where('name', 'Andi')->where('classroom_id', $kelas->id)->first();
                if ($andi && ! $andi->parent_id) {
                    $andi->update(['parent_id' => $wali->id]);
                }
            }
        }

        $lpiStudent = Student::where('name', 'Santri LPI')->first();
        if (! $lpiStudent) {
            Student::create([
                'name' => 'Santri LPI',
                'nis' => 'LPI'.rand(1000, 9999),
                'nisn' => '0079990001',
                'gender' => 'L',
                'classroom_id' => $kelasLpi->id,
                'status' => 'aktif',
            ]);
        }

        FinanceUnitSetting::firstOrCreate(['unit' => 'sma'], ['opening_balance' => 5000000]);
        FinanceUnitSetting::firstOrCreate(['unit' => 'lpi'], ['opening_balance' => 2500000]);
    }
}
