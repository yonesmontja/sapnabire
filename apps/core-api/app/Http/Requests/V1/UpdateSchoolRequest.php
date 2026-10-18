<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    public function rules(): array
    {
        $school = $this->user()->school;

        return [
            'npsn' => ['sometimes', 'digits:8', Rule::unique('schools', 'npsn')->ignore($school)],
            'name' => ['sometimes', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'level' => ['sometimes', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'accreditation' => ['nullable', Rule::in(['A', 'B', 'C', 'TT'])],
            'foundation_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'url'],
            'timezone' => ['sometimes', 'timezone:per_country,ID'],
            'principal_employee_id' => ['nullable', Rule::exists('employees', 'id')->where('school_id', $school->id)],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'settings' => ['sometimes', 'array'],
            'settings.kktp_default' => ['nullable', 'integer', 'between:0,100'],
            'settings.report_city' => ['nullable', 'string', 'max:100'],
        ];
    }
}
