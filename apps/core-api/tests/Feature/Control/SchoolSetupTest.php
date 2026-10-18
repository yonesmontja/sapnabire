<?php

namespace Tests\Feature\Control;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Employee;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolSetupTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
    }

    private function actingAsRole(UserRole $role, ?School $school = null): User
    {
        $user = User::factory()->create(['school_id' => ($school ?? $this->school)->id, 'role' => $role]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createYear(bool $active = true): AcademicYear
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'starts_on' => '2026-07-13', 'ends_on' => '2027-06-30']);
        if ($active) {
            $year->activate();
        }

        return $year;
    }

    public function test_login_with_username_returns_token(): void
    {
        User::factory()->create(['school_id' => $this->school->id, 'username' => 'guru1', 'password' => 'rahasia123']);

        $this->postJson('/api/v1/auth/login', ['login' => 'guru1', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'role', 'school_id']]);

        $this->postJson('/api/v1/auth/login', ['login' => 'guru1', 'password' => 'salah'])
            ->assertUnprocessable();
    }

    public function test_admin_can_update_school_profile_and_merge_settings(): void
    {
        $this->school->update(['settings' => ['kktp_default' => 70, 'report_city' => 'Nabire']]);
        $this->actingAsRole(UserRole::SchoolAdmin);

        $this->patchJson('/api/v1/control/school', ['name' => 'SD Anak Papua', 'settings' => ['kktp_default' => 75]])
            ->assertOk()
            ->assertJsonPath('data.name', 'SD Anak Papua')
            ->assertJsonPath('data.settings.kktp_default', 75)
            ->assertJsonPath('data.settings.report_city', 'Nabire');

        $this->assertDatabaseHas('activity_logs', ['module' => 'setup_sekolah', 'event' => 'updated']);
    }

    public function test_teacher_can_read_but_not_modify_setup(): void
    {
        $this->actingAsRole(UserRole::Teacher);

        $this->getJson('/api/v1/control/school')->assertOk();
        $this->patchJson('/api/v1/control/school', ['name' => 'X'])->assertForbidden();
        $this->postJson('/api/v1/control/departments', ['code' => 'KUR', 'name' => 'Kurikulum'])->assertForbidden();
    }

    public function test_academic_year_is_created_with_semesters_and_only_one_is_active(): void
    {
        $this->actingAsRole(UserRole::SchoolAdmin);
        $old = $this->createYear();

        $id = $this->postJson('/api/v1/control/academic-years', [
            'name' => '2027/2028', 'starts_on' => '2027-07-12', 'ends_on' => '2028-06-30',
            'semesters' => [
                ['term' => 1, 'starts_on' => '2027-07-12', 'ends_on' => '2027-12-18'],
                ['term' => 2, 'starts_on' => '2028-01-03', 'ends_on' => '2028-06-30'],
            ],
        ])->assertCreated()->assertJsonCount(2, 'data.semesters')->json('data.id');

        $this->postJson("/api/v1/control/academic-years/{$id}/activate")->assertOk()->assertJsonPath('data.is_active', true);
        $this->assertFalse($old->fresh()->is_active);
    }

    public function test_department_tree_with_active_pics(): void
    {
        $this->actingAsRole(UserRole::Principal);
        $year = $this->createYear();
        $employee = Employee::factory()->create(['school_id' => $this->school->id]);

        $parentId = $this->postJson('/api/v1/control/departments', ['code' => 'KUR', 'name' => 'Kurikulum'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/control/departments', ['code' => 'ASM', 'name' => 'Asesmen', 'parent_id' => $parentId])->assertCreated();

        $this->postJson("/api/v1/control/departments/{$parentId}/pics", [
            'employee_id' => $employee->id, 'academic_year_id' => $year->id, 'role' => 'lead', 'started_on' => '2026-07-13',
        ])->assertCreated()->assertJsonPath('data.raci', 'A');

        $this->getJson('/api/v1/control/departments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.children.0.code', 'ASM')
            ->assertJsonPath('data.0.pics.0.employee.id', $employee->id);
    }

    public function test_only_one_active_lead_per_department_and_year(): void
    {
        $this->actingAsRole(UserRole::SchoolAdmin);
        $year = $this->createYear();
        $department = Department::create(['school_id' => $this->school->id, 'code' => 'KES', 'name' => 'Kesiswaan']);
        [$a, $b] = Employee::factory()->count(2)->create(['school_id' => $this->school->id]);
        $url = "/api/v1/control/departments/{$department->id}/pics";
        $payload = ['academic_year_id' => $year->id, 'role' => 'lead', 'started_on' => '2026-07-13'];

        $picId = $this->postJson($url, [...$payload, 'employee_id' => $a->id])->assertCreated()->json('data.id');
        $this->postJson($url, [...$payload, 'employee_id' => $b->id])->assertUnprocessable()->assertJsonValidationErrors('role');

        // Setelah penugasan lama diakhiri, PIC utama baru boleh ditetapkan dan histori tetap ada.
        $this->postJson("{$url}/{$picId}/end", ['ended_on' => '2026-09-30'])->assertOk()->assertJsonPath('data.ended_on', '2026-09-30');
        $this->postJson($url, [...$payload, 'employee_id' => $b->id, 'started_on' => '2026-10-01'])->assertCreated();
        $this->assertDatabaseCount('department_pics', 2);
    }

    public function test_cannot_delete_employee_who_is_active_pic(): void
    {
        $this->actingAsRole(UserRole::SchoolAdmin);
        $year = $this->createYear();
        $employee = Employee::factory()->create(['school_id' => $this->school->id]);
        $department = Department::create(['school_id' => $this->school->id, 'code' => 'SAR', 'name' => 'Sarpras']);
        $department->pics()->create(['employee_id' => $employee->id, 'academic_year_id' => $year->id, 'role' => 'member', 'raci' => 'R', 'started_on' => '2026-07-13']);

        $this->deleteJson("/api/v1/control/employees/{$employee->id}")->assertUnprocessable();
    }

    public function test_tenant_isolation_between_schools(): void
    {
        $otherSchool = School::factory()->create();
        $foreignEmployee = Employee::factory()->create(['school_id' => $otherSchool->id]);
        $foreignDepartment = Department::create(['school_id' => $otherSchool->id, 'code' => 'KUR', 'name' => 'Kurikulum']);
        $this->actingAsRole(UserRole::SchoolAdmin);
        $year = $this->createYear();

        $this->getJson("/api/v1/control/employees/{$foreignEmployee->id}")->assertNotFound();
        $this->getJson("/api/v1/control/departments/{$foreignDepartment->id}")->assertNotFound();
        $this->getJson('/api/v1/control/employees')->assertOk()->assertJsonCount(0, 'data');

        $ownDepartment = Department::create(['school_id' => $this->school->id, 'code' => 'KUR', 'name' => 'Kurikulum']);
        $this->postJson("/api/v1/control/departments/{$ownDepartment->id}/pics", [
            'employee_id' => $foreignEmployee->id, 'academic_year_id' => $year->id, 'role' => 'member', 'started_on' => '2026-07-13',
        ])->assertUnprocessable()->assertJsonValidationErrors('employee_id');
    }
}
