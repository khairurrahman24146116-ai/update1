<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;

if (! function_exists('activity_log')) {
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    function activity_log(
        string $action,
        User|int|null $user = null,
        ?string $targetType = null,
        int|string|null $targetId = null,
        ?array $metadata = null,
        ?string $description = null
    ): ?ActivityLog {
        return ActivityLogger::log($action, $user, $targetType, $targetId, $metadata, $description);
    }
}
