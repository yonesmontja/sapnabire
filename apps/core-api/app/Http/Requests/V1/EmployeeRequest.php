<?php

namespace App\Http\Requests\V1;

use App\Enums\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'employee_number' => [
                'nullable', 'string', 'max:30',
                Rule::unique('employees')
                    ->where('school_id', $this->user()->school_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->route('employee')),
            ],
            'nuptk' => ['nullable', 'digits:16'],
            'full_name' => [$required, 'string', 'max:255'],
            'front_title' => ['nullable', 'string', 'max:30'],
            'back_title' => ['nullable', 'string', 'max:50'],
            'gender' => [$required, Rule::in(['L', 'P'])],
            'employment_type' => [$required, Rule::enum(EmploymentType::class)],
            'position' => ['nullable', 'string', 'max:255'],
            'is_teacher' => ['boolean'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_on' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
