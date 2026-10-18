<?php

namespace App\Http\Resources\V1;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AcademicYear */
class AcademicYearResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'is_active' => $this->is_active,
            'semesters' => $this->whenLoaded('semesters', fn () => $this->semesters->map(fn ($s) => [
                'id' => $s->id,
                'term' => $s->term,
                'label' => $s->term === 1 ? 'Ganjil' : 'Genap',
                'starts_on' => $s->starts_on->toDateString(),
                'ends_on' => $s->ends_on->toDateString(),
                'is_active' => $s->is_active,
            ])),
        ];
    }
}
