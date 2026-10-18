<?php

use App\Http\Controllers\Api\Internal\AssessmentCallbackController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\Assessment\GeneratorController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DepartmentPicController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\SchoolController;
use Illuminate\Support\Facades\Route;

/*
| API Gateway EduERP — v1. Daftar lengkap modul (termasuk yang belum diimplementasi)
| ada di docs/03-api-gateway.md. Semua route di sini ter-prefix /api.
*/

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');

    Route::middleware(['auth:sanctum', 'school'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // ===== SMART Control: Setup & Struktur =====
        Route::prefix('control')->name('control.')->group(function () {
            // Baca: semua pegawai sekolah
            Route::get('school', [SchoolController::class, 'show'])->name('school.show');
            Route::get('academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
            Route::get('departments/{department}/pics', [DepartmentPicController::class, 'index'])->name('departments.pics.index');

            // Tulis: admin sekolah & kepala sekolah
            Route::middleware('role:school_admin,principal')->group(function () {
                Route::patch('school', [SchoolController::class, 'update'])->name('school.update');
                Route::post('academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
                Route::post('academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
                Route::apiResource('employees', EmployeeController::class)->except(['index', 'show']);
                Route::apiResource('departments', DepartmentController::class)->except(['index', 'show']);
                Route::post('departments/{department}/pics', [DepartmentPicController::class, 'store'])->name('departments.pics.store');
                Route::post('departments/{department}/pics/{pic}/end', [DepartmentPicController::class, 'end'])->name('departments.pics.end');
            });
        });

        // ===== Smart Assessment (diteruskan ke Django) =====
        Route::prefix('assessment')->name('assessment.')
            ->middleware('role:teacher,principal,school_admin')
            ->group(function () {
                Route::post('generator/jobs', [GeneratorController::class, 'store'])->middleware('throttle:10,1')->name('generator.store');
                Route::get('generator/jobs/{job}', [GeneratorController::class, 'show'])->whereUuid('job')->name('generator.show');
                Route::get('generator/jobs/{job}/questions', [GeneratorController::class, 'questions'])->whereUuid('job')->name('generator.questions');
            });
    });
});

// Webhook antar-service (bukan untuk browser). Dilindungi HMAC, bukan token user.
Route::post('internal/assessment/callbacks', AssessmentCallbackController::class)
    ->middleware('service.signature')
    ->name('internal.assessment.callbacks');
