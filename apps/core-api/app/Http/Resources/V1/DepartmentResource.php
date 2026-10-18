<?php

namespace App\Http\Resources\V1;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Department */
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(['id', 'parent_id', 'code', 'name', 'description', 'color', 'sort_order', 'is_active']),
            'children' => self::collection($this->whenLoaded('children')),
            'pics' => DepartmentPicResource::collection($this->whenLoaded('activePics')),
        ];
    }
}
