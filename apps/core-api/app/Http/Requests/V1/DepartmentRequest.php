<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'parent_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('school_id', $schoolId),
                Rule::notIn([$this->route('department')?->getKey()]),
            ],
            'code' => [
                $required, 'string', 'max:20', 'alpha_dash:ascii',
                Rule::unique('departments')->where('school_id', $schoolId)->ignore($this->route('department')),
            ],
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'hex_color'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
