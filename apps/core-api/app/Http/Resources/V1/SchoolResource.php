<?php

namespace App\Http\Resources\V1;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin School */
class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only([
                'id', 'npsn', 'name', 'short_name', 'level', 'accreditation', 'foundation_name',
                'address', 'village', 'district', 'city', 'province', 'postal_code',
                'phone', 'email', 'website', 'logo_path', 'timezone', 'vision', 'mission', 'settings', 'is_active',
            ]),
            'principal' => EmployeeResource::make($this->whenLoaded('principal')),
            'active_academic_year' => AcademicYearResource::make($this->whenLoaded('activeAcademicYear')),
        ];
    }
}
