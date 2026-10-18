<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('academic_years')->where('school_id', $this->user()->school_id),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'semesters' => ['required', 'array', 'size:2'],
            'semesters.*.term' => ['required', 'integer', 'distinct', Rule::in([1, 2])],
            'semesters.*.starts_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'semesters.*.ends_on' => ['required', 'date', 'before_or_equal:ends_on'],
        ];
    }
}
