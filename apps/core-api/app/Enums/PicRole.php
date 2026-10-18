<?php

namespace App\Enums;

enum PicRole: string
{
    case Lead = 'lead';         // PIC utama — satu per bidang per tahun ajaran
    case Deputy = 'deputy';     // Wakil PIC
    case Member = 'member';     // Anggota tim
    case Advisor = 'advisor';   // Pembina / konsultan

    /** Peran RACI bawaan saat PIC ditambahkan tanpa memilih RACI. */
    public function defaultRaci(): string
    {
        return match ($this) {
            self::Lead => 'A',
            self::Deputy, self::Member => 'R',
            self::Advisor => 'C',
        };
    }
}
