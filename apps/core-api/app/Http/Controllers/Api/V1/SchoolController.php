<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UpdateSchoolRequest;
use App\Http\Resources\V1\SchoolResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/** SMART Control — Setup Sekolah (profil sekolah milik user yang login). */
class SchoolController extends Controller
{
    public function show(Request $request): SchoolResource
    {
        return SchoolResource::make($request->user()->school->load(['principal', 'activeAcademicYear.semesters']));
    }

    public function update(UpdateSchoolRequest $request): SchoolResource
    {
        $school = $request->user()->school;
        $data = $request->validated();

        if (isset($data['settings'])) {
            $data['settings'] = array_replace($school->settings ?? [], $data['settings']);
        }

        $school->update($data);
        ActivityLog::record($request, 'setup_sekolah', 'updated', $school, ['fields' => array_keys($data)]);

        return SchoolResource::make($school->load(['principal', 'activeAcademicYear.semesters']));
    }
}
