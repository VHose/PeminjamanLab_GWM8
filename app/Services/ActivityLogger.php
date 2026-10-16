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
        // Jika ada data array dan belum ada description, format jadi text ringkas
        if ($description === null && !empty($data)) {
            $description = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return ActivityLog::record(
            $userId,
            $action,
            $entityType,
            $entityId,
            $description,
            request()?->ip()
        );
    }
}
