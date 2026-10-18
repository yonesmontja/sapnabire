<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

#[Fillable(['school_id', 'user_id', 'module', 'event', 'subject_type', 'subject_id', 'properties', 'ip_address'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public static function record(Request $request, string $module, string $event, ?Model $subject = null, array $properties = []): self
    {
        return static::create([
            'school_id' => $request->user()?->school_id,
            'user_id' => $request->user()?->getKey(),
            'module' => $module,
            'event' => $event,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
            'ip_address' => $request->ip(),
        ]);
    }
}
