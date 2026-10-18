<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DepartmentRequest;
use App\Http\Resources\V1\DepartmentResource;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** SMART Control — Master Bidang. */
class DepartmentController extends Controller
{
    /** Default: pohon bidang (root + children) beserta PIC aktif. `?flat=1` untuk dropdown. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Department::forSchool($request->user()->school_id)->orderBy('sort_order')->orderBy('name');

        if ($request->boolean('flat')) {
            return DepartmentResource::collection($query->get());
        }

        return DepartmentResource::collection(
            $query->whereNull('parent_id')
                ->with(['activePics.employee', 'children.activePics.employee'])
                ->get()
        );
    }

    public function store(DepartmentRequest $request): DepartmentResource
    {
        $department = Department::create([...$request->validated(), 'school_id' => $request->user()->school_id]);
        ActivityLog::record($request, 'master_bidang', 'created', $department);

        return DepartmentResource::make($department);
    }

    public function show(Request $request, Department $department): DepartmentResource
    {
        $this->ensureSameSchool($request, $department->school_id);

        return DepartmentResource::make($department->load(['children', 'activePics.employee']));
    }

    public function update(DepartmentRequest $request, Department $department): DepartmentResource
    {
        $this->ensureSameSchool($request, $department->school_id);
        $department->update($request->validated());
        ActivityLog::record($request, 'master_bidang', 'updated', $department, ['fields' => array_keys($request->validated())]);

        return DepartmentResource::make($department);
    }

    public function destroy(Request $request, Department $department): Response
    {
        $this->ensureSameSchool($request, $department->school_id);
        abort_if($department->children()->exists(), 422, 'Bidang masih memiliki sub-bidang.');

        $department->delete();
        ActivityLog::record($request, 'master_bidang', 'deleted', $department);

        return response()->noContent();
    }
}
