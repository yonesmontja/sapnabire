<?php

namespace App\Http\Resources\V1;

use App\Models\DepartmentPic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DepartmentPic */
class DepartmentPicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'academic_year_id' => $this->academic_year_id,
            'role' => $this->role->value,
            'raci' => $this->raci,
            'decree_number' => $this->decree_number,
            'started_on' => $this->started_on->toDateString(),
            'ended_on' => $this->ended_on?->toDateString(),
            'notes' => $this->notes,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
        ];
    }
}
