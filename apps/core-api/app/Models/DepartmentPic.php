<?php

namespace App\Models;

use App\Enums\PicRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'department_id', 'employee_id', 'academic_year_id', 'role', 'raci',
    'decree_number', 'started_on', 'ended_on', 'notes',
])]
class DepartmentPic extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'role' => PicRole::class,
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
