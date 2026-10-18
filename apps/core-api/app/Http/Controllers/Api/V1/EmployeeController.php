<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\EmployeeRequest;
use App\Http\Resources\V1\EmployeeResource;
use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $employees = Employee::forSchool($request->user()->school_id)
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('full_name', 'ilike', "%{$term}%")
                ->orWhere('employee_number', 'ilike', "%{$term}%")))
            ->when($request->has('is_teacher'), fn ($q) => $q->where('is_teacher', $request->boolean('is_teacher')))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('full_name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return EmployeeResource::collection($employees);
    }

    public function store(EmployeeRequest $request): EmployeeResource
    {
        $employee = Employee::create([...$request->validated(), 'school_id' => $request->user()->school_id]);
        ActivityLog::record($request, 'master_pegawai', 'created', $employee);

        return EmployeeResource::make($employee);
    }

    public function show(Request $request, Employee $employee): EmployeeResource
    {
        $this->ensureSameSchool($request, $employee->school_id);

        return EmployeeResource::make($employee);
    }

    public function update(EmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $this->ensureSameSchool($request, $employee->school_id);
        $employee->update($request->validated());
        ActivityLog::record($request, 'master_pegawai', 'updated', $employee, ['fields' => array_keys($request->validated())]);

        return EmployeeResource::make($employee);
    }

    public function destroy(Request $request, Employee $employee): Response
    {
        $this->ensureSameSchool($request, $employee->school_id);
        abort_if($employee->picAssignments()->whereNull('ended_on')->exists(), 422, 'Pegawai masih menjabat PIC aktif. Akhiri penugasannya terlebih dahulu.');

        $employee->delete();
        ActivityLog::record($request, 'master_pegawai', 'deleted', $employee);

        return response()->noContent();
    }
}
