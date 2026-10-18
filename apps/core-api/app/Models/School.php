<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'npsn', 'name', 'short_name', 'level', 'accreditation', 'foundation_name',
    'address', 'village', 'district', 'city', 'province', 'postal_code',
    'phone', 'email', 'website', 'logo_path', 'timezone', 'principal_employee_id',
    'vision', 'mission', 'settings', 'is_active',
])]
class School extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $attributes = [
        'settings' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'principal_employee_id');
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function activeAcademicYear(): HasOne
    {
        return $this->hasOne(AcademicYear::class)->where('is_active', true);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
