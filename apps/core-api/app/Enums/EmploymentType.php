<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Pns = 'pns';
    case Pppk = 'pppk';
    case Gty = 'gty';         // Guru Tetap Yayasan
    case Gtt = 'gtt';         // Guru Tidak Tetap
    case Pty = 'pty';         // Pegawai Tetap Yayasan (tendik)
    case Honorary = 'honorary';
}
