<?php

namespace App\Models;

use App\Enums\PicRole;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['school_id', 'parent_id', 'code', 'name', 'description', 'color', 'sort_order', 'is_active'])]
class Department extends Model
{
    use BelongsToSchool, HasUlids, SoftDeletes;

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function pics(): HasMany
    {
        return $this->hasMany(DepartmentPic::class);
    }

    public function activePics(): HasMany
    {
        return $this->pics()->whereNull('ended_on');
    }

    public function lead(): HasMany
    {
        return $this->activePics()->where('role', PicRole::Lead);
    }
}
