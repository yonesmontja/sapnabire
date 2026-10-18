<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\PicRole;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Employee;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data awal SMART Control: sekolah, tahun ajaran, bidang standar, dan akun admin.
 * Password admin dibuat acak dan dicetak sekali di konsol (pengganti akun "admin" di sheet Users lama).
 */
class SchoolSetupSeeder extends Seeder
{
    /** Bidang standar sekolah — bisa diubah dari menu Master Bidang. */
    private const DEPARTMENTS = [
        ['KUR', 'Kurikulum', '#2563EB'],
        ['KES', 'Kesiswaan', '#16A34A'],
        ['SAR', 'Sarana & Prasarana', '#D97706'],
        ['HUM', 'Hubungan Masyarakat', '#9333EA'],
        ['KEU', 'Keuangan', '#DC2626'],
        ['SDM', 'SDM & Kinerja', '#0891B2'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $school = School::firstOrCreate(['npsn' => '00000000'], [
                'name' => 'SD Sekolah Anak Papua',
                'short_name' => 'SAP',
                'level' => 'SD',
                'city' => 'Nabire',
                'province' => 'Papua Tengah',
                'timezone' => 'Asia/Jayapura',
                'settings' => ['kktp_default' => 75, 'report_city' => 'Nabire'],
            ]);

            $year = AcademicYear::firstOrCreate(
                ['school_id' => $school->id, 'name' => '2026/2027'],
                ['starts_on' => '2026-07-13', 'ends_on' => '2027-06-30'],
            );
            if ($year->wasRecentlyCreated) {
                $year->semesters()->createMany([
                    ['term' => 1, 'starts_on' => '2026-07-13', 'ends_on' => '2026-12-19', 'is_active' => true],
                    ['term' => 2, 'starts_on' => '2027-01-04', 'ends_on' => '2027-06-30'],
                ]);
                $year->activate();
            }

            $principal = Employee::firstOrCreate(
                ['school_id' => $school->id, 'full_name' => 'Kepala Sekolah'],
                ['gender' => 'L', 'employment_type' => EmploymentType::Gty, 'position' => 'Kepala Sekolah', 'back_title' => 'S.Pd.'],
            );
            $school->update(['principal_employee_id' => $principal->id]);

            foreach (self::DEPARTMENTS as $i => [$code, $name, $color]) {
                $department = Department::firstOrCreate(
                    ['school_id' => $school->id, 'code' => $code],
                    ['name' => $name, 'color' => $color, 'sort_order' => $i],
                );
                // Kepala sekolah menjadi Accountable default di semua bidang sampai PIC ditetapkan.
                $department->pics()->firstOrCreate(
                    ['employee_id' => $principal->id, 'academic_year_id' => $year->id],
                    ['role' => PicRole::Advisor, 'raci' => 'A', 'started_on' => $year->starts_on],
                );
            }

            if (! User::where('username', 'admin')->exists()) {
                $password = Str::password(16);
                User::create([
                    'school_id' => $school->id,
                    'name' => 'Administrator',
                    'username' => 'admin',
                    'email' => 'admin@eduerp.local',
                    'password' => $password,
                    'role' => UserRole::SchoolAdmin,
                ]);
                $this->command?->warn("Akun admin dibuat → username: admin | password: {$password} (segera ganti)");
            }
        });
    }
}
