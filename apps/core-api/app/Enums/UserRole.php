<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';   // Pengelola platform / yayasan
    case SchoolAdmin = 'school_admin'; // Operator sekolah
    case Principal = 'principal';      // Kepala sekolah (Dashboard Pimpinan)
    case Teacher = 'teacher';
    case Staff = 'staff';              // Tendik: TU, keuangan, sarpras
    case Student = 'student';
    case Guardian = 'guardian';

    /** Peran yang boleh mengubah Setup Sekolah & Master PIC. */
    public static function setupManagers(): array
    {
        return [self::SuperAdmin, self::SchoolAdmin, self::Principal];
    }
}
