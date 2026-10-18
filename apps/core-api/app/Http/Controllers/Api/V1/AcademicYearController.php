<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreAcademicYearRequest;
use App\Http\Resources\V1\AcademicYearResource;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AcademicYearResource::collection(
            AcademicYear::forSchool($request->user()->school_id)->with('semesters')->latest('starts_on')->get()
        );
    }

    public function store(StoreAcademicYearRequest $request): AcademicYearResource
    {
        $year = DB::transaction(function () use ($request) {
            $year = AcademicYear::create([
                ...$request->safe()->except('semesters'),
                'school_id' => $request->user()->school_id,
            ]);
            $year->semesters()->createMany($request->validated('semesters'));

            return $year;
        });

        ActivityLog::record($request, 'setup_sekolah', 'academic_year_created', $year);

        return AcademicYearResource::make($year->load('semesters'));
    }

    public function activate(Request $request, AcademicYear $academicYear): AcademicYearResource
    {
        $this->ensureSameSchool($request, $academicYear->school_id);
        $academicYear->activate();
        ActivityLog::record($request, 'setup_sekolah', 'academic_year_activated', $academicYear);

        return AcademicYearResource::make($academicYear->load('semesters'));
    }
}
