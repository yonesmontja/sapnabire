<?php

namespace App\Http\Requests\V1;

use App\Enums\PicRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentPicRequest extends FormRequest
{
    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'role' => ['required', Rule::enum(PicRole::class)],
            'raci' => ['nullable', Rule::in(['R', 'A', 'C', 'I'])],
            'decree_number' => ['nullable', 'string', 'max:255'],
            'started_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
