<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** Cegah akses lintas sekolah lewat route model binding (respon 404 agar ID tenant lain tidak bocor). */
    protected function ensureSameSchool(Request $request, ?string $schoolId): void
    {
        abort_unless($schoolId === $request->user()->school_id, 404);
    }
}
