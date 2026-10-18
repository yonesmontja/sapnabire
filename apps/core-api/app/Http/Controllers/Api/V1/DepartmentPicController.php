<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PicRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreDepartmentPicRequest;
use App\Http\Resources\V1\DepartmentPicResource;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\DepartmentPic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/** SMART Control — Master PIC (penugasan pegawai ke bidang). */
class DepartmentPicController extends Controller
{
    public function index(Request $request, Department $department): AnonymousResourceCollection
    {
        $this->ensureSameSchool($request, $department->school_id);

        $pics = $department->pics()
            ->with('employee')
            ->when($request->query('academic_year_id'), fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when(! $request->boolean('include_ended'), fn ($q) => $q->whereNull('ended_on'))
            ->orderByRaw("CASE role WHEN 'lead' THEN 0 WHEN 'deputy' THEN 1 WHEN 'member' THEN 2 ELSE 3 END")
            ->get();

        return DepartmentPicResource::collection($pics);
    }

    public function store(StoreDepartmentPicRequest $request, Department $department): DepartmentPicResource
    {
        $this->ensureSameSchool($request, $department->school_id);
        $data = $request->validated();
        $role = PicRole::from($data['role']);

        $active = $department->activePics()->where('academic_year_id', $data['academic_year_id']);

        if ($role === PicRole::Lead && (clone $active)->where('role', PicRole::Lead)->exists()) {
            throw ValidationException::withMessages(['role' => 'Bidang ini sudah memiliki PIC utama aktif. Akhiri penugasan sebelumnya terlebih dahulu.']);
        }
        if ((clone $active)->where('employee_id', $data['employee_id'])->exists()) {
            throw ValidationException::withMessages(['employee_id' => 'Pegawai ini sudah ditugaskan di bidang ini.']);
        }

        $pic = $department->pics()->create([...$data, 'raci' => $data['raci'] ?? $role->defaultRaci()]);
        ActivityLog::record($request, 'master_pic', 'assigned', $pic, ['employee_id' => $pic->employee_id, 'role' => $role->value]);

        return DepartmentPicResource::make($pic->load('employee'));
    }

    /** Mengakhiri penugasan (bukan hapus) agar histori PIC tetap tercatat. */
    public function end(Request $request, Department $department, DepartmentPic $pic): DepartmentPicResource
    {
        $this->ensureSameSchool($request, $department->school_id);
        abort_unless($pic->department_id === $department->id, 404);

        $validated = $request->validate(['ended_on' => ['nullable', 'date', 'after_or_equal:'.$pic->started_on->toDateString()]]);
        $pic->update(['ended_on' => $validated['ended_on'] ?? now()->toDateString()]);
        ActivityLog::record($request, 'master_pic', 'ended', $pic);

        return DepartmentPicResource::make($pic->load('employee'));
    }
}
