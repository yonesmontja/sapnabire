<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['school_id', 'name', 'starts_on', 'ends_on', 'is_active'])]
class AcademicYear extends Model
{
    use BelongsToSchool, HasUlids;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class)->orderBy('term');
    }

    /** Aktifkan tahun ini dan nonaktifkan tahun lain di sekolah yang sama, secara atomik. */
    public function activate(): void
    {
        DB::transaction(function () {
            static::forSchool($this->school_id)->whereKeyNot($this->getKey())->update(['is_active' => false]);
            $this->forceFill(['is_active' => true])->save();
        });
    }
}
