<?php

namespace App\Services\Activity;

use App\Models\Activity\ActivityLog;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Activity
{
    public function log(
        string $action, ?int $user,
        Model $subject, ?string $description,
        array $properties = []
    )
    {
        return ActivityLog::create([
            'user_id' => $user,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => $description,
            'properties' => $properties
        ]);
    }
}
