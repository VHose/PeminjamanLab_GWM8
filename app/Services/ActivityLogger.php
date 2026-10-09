<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public function log(
        ?string $userId,
        string $action,
        string $entityType,
        string|int $entityId,
        ?string $description = null,
        ?array $data = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'data' => $data ?? ($description ? ['description' => $description] : null),
            'created_at' => now(),
        ]);
    }
}
