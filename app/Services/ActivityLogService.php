<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Support\ActivityLogCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function filtered(array $filters): Builder
    {
        return ActivityLog::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $like = '%'.$search.'%';
                    $nested->where('action', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('user_name', 'like', $like)
                        ->orWhere('user_role', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhere('target_type', 'like', $like)
                        ->orWhere('metadata', 'like', $like);
                });
            })
            ->when($filters['module'] ?? null, fn (Builder $query, string $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{actions: array<int, array{value: string, label: string, module: string}>, modules: array<int, array{value: string, label: string}>, users: array<int, array{id: int, name: string, role: string|null}>}
     */
    public function options(): array
    {
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action): array => [
                'value' => $action,
                'label' => ActivityLogCatalog::descriptionFor($action),
                'module' => ActivityLogCatalog::moduleFor($action),
            ])
            ->values()
            ->all();

        $modules = ActivityLog::query()
            ->whereNotNull('module')
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->map(fn (string $module): array => [
                'value' => $module,
                'label' => ActivityLogCatalog::moduleLabel($module),
            ])
            ->values()
            ->all();

        $users = ActivityLog::query()
            ->whereNotNull('user_id')
            ->select(['user_id', 'user_name', 'user_role'])
            ->orderBy('user_name')
            ->get()
            ->unique('user_id')
            ->map(fn (ActivityLog $log): array => [
                'id' => (int) $log->user_id,
                'name' => $log->user_name ?: 'Tanpa nama',
                'role' => $log->user_role,
            ])
            ->values()
            ->all();

        return ['actions' => $actions, 'modules' => $modules, 'users' => $users];
    }

    public function retentionMonths(): int
    {
        $setting = SiteSetting::query()->first()?->activity_log_retention_months;

        return $this->clampMonths((int) ($setting ?: config('activity-log.default_retention_months')));
    }

    public function updateRetention(int $months): int
    {
        $months = $this->clampMonths($months);
        $setting = SiteSetting::query()->firstOrCreate(
            ['id' => 1],
            ['app_name' => config('app.name')],
        );
        $setting->update(['activity_log_retention_months' => $months]);

        return $months;
    }

    /**
     * @return array{months: int, cutoff: string, count: int}
     */
    public function retentionPreview(?int $months = null): array
    {
        $months = $this->clampMonths($months ?? $this->retentionMonths());
        $cutoff = now()->subMonths($months)->startOfDay();

        return [
            'months' => $months,
            'cutoff' => $cutoff->toDateString(),
            'count' => ActivityLog::query()->where('created_at', '<', $cutoff)->count(),
        ];
    }

    public function prune(int $months): int
    {
        $cutoff = now()->subMonths($this->clampMonths($months))->startOfDay();

        return ActivityLog::query()->where('created_at', '<', $cutoff)->delete();
    }

    public function clampMonths(int $months): int
    {
        return min(max($months, 1), (int) config('activity-log.max_retention_months', 120));
    }
}
