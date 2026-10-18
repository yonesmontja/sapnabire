<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id', 'user_id', 'employee_number', 'nuptk', 'full_name', 'front_title', 'back_title',
    'gender', 'employment_type', 'position', 'is_teacher', 'email', 'phone', 'joined_on', 'is_active',
])]
class Employee extends Model
{
    use BelongsToSchool, HasFactory, HasUlids, SoftDeletes;

    protected $attributes = [
        'is_teacher' => true,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'is_teacher' => 'boolean',
            'is_active' => 'boolean',
            'joined_on' => 'date',
        ];
    }

    /** Nama lengkap bergelar untuk dokumen cetak (rapor, SK, notulen). */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => trim(
            collect([$this->front_title, $this->full_name])->filter()->implode(' ')
            .($this->back_title ? ', '.$this->back_title : '')
        ));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function picAssignments(): HasMany
    {
        return $this->hasMany(DepartmentPic::class);
    }
}
