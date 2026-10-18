<?php

namespace App\Models\Concerns;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolasi tenant: semua query modul operasional wajib lewat scope `forSchool`.
 */
trait BelongsToSchool
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForSchool(Builder $query, School|string $school): Builder
    {
        return $query->where($this->qualifyColumn('school_id'), $school instanceof School ? $school->getKey() : $school);
    }
}
