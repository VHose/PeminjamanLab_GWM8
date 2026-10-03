<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    /**
     * Record an auditable application action in one consistent place.
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        int $entityId,
        ?string $description = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
        ]);
    }
}
