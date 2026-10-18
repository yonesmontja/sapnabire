<?php

namespace App\Http\Resources\V1;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only([
                'id', 'employee_number', 'nuptk', 'full_name', 'front_title', 'back_title',
                'gender', 'position', 'is_teacher', 'email', 'phone', 'is_active',
            ]),
            'display_name' => $this->display_name,
            'employment_type' => $this->employment_type?->value,
            'joined_on' => $this->joined_on?->toDateString(),
            'has_account' => $this->user_id !== null,
        ];
    }
}
