<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use App\Support\ActivityLogCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogger
{
    /**
     * Catat aktivitas tanpa pernah menggagalkan aksi utama.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        string $action,
        User|int|null $user = null,
        ?string $targetType = null,
        int|string|null $targetId = null,
        ?array $metadata = null,
        ?string $description = null
    ): ?ActivityLog {
        return self::write($action, $user, $targetType, $targetId, $metadata, $description);
    }

    /**
     * Catat aktivitas tamu, misalnya percobaan login dengan akun tak dikenal.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function logGuest(string $action, ?array $metadata = null, ?string $description = null): ?ActivityLog
    {
        return self::write($action, null, null, null, $metadata, $description, 'Tamu');
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private static function write(
        string $action,
        User|int|null $user,
        ?string $targetType,
        int|string|null $targetId,
        ?array $metadata,
        ?string $description,
        ?string $actorName = null
    ): ?ActivityLog {
        try {
            $actor = self::resolveActor($user);

            return ActivityLog::create([
                'user_id' => $actor?->id,
                'unit_id' => self::resolveUnitId($actor, $metadata),
                'user_name' => $actor?->name ?? $actorName,
                'user_role' => $actor?->role,
                'action' => $action,
                'module' => ActivityLogCatalog::moduleFor($action),
                'description' => Str::limit($description ?? ActivityLogCatalog::descriptionFor($action), 250, ''),
                'target_type' => $targetType,
                'target_id' => is_numeric($targetId) ? (int) $targetId : null,
                'metadata' => $metadata === null ? null : ActivityLogCatalog::sanitizeMetadata($metadata),
                'ip_address' => self::requestIp(),
                'user_agent' => Str::limit((string) self::requestUserAgent(), 1000, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('activity log failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private static function resolveUnitId(?User $actor, ?array $metadata): ?int
    {
        $code = $metadata['unit'] ?? null;

        if ($code === null && $actor !== null && ! $actor->isAdmin()) {
            $code = $actor->effectiveUnitCodes()[0] ?? null;
        }

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        return Unit::query()
            ->whereRaw('LOWER(kode) = ?', [strtolower(trim($code))])
            ->value('id');
    }

    private static function resolveActor(User|int|null $user): ?User
    {
        if ($user instanceof User) {
            return $user;
        }

        if (is_int($user)) {
            return User::find($user);
        }

        return Auth::user();
    }

    private static function requestIp(): ?string
    {
        try {
            return app()->bound('request') ? request()->ip() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function requestUserAgent(): ?string
    {
        try {
            return app()->bound('request') ? request()->userAgent() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
