<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Unit;
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
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('user_role', $role))
            ->when($filters['unit'] ?? null, function (Builder $query, string $unit): void {
                $unitId = Unit::query()
                    ->whereRaw('LOWER(kode) = ?', [strtolower($unit)])
                    ->value('id');

                $unitId === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('unit_id', $unitId);
            })
            ->when($filters['only_operators'] ?? false, fn (Builder $query) => $query->where('user_role', 'operator'))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{actions: array<int, array{value: string, label: string, module: string}>, modules: array<int, array{value: string, label: string}>, roles: array<int, array{value: string, label: string}>, units: array<int, array{value: string, label: string}>, users: array<int, array{id: int, name: string, role: string|null}>}
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

        $roles = ActivityLog::query()
            ->whereNotNull('user_role')
            ->select('user_role')
            ->distinct()
            ->orderBy('user_role')
            ->pluck('user_role')
            ->map(fn (string $role): array => [
                'value' => $role,
                'label' => ActivityLogCatalog::roleLabel($role),
            ])
            ->values()
            ->all();

        $units = Unit::query()
            ->orderBy('kode')
            ->get(['kode', 'nama'])
            ->map(fn (Unit $unit): array => [
                'value' => strtolower($unit->kode),
                'label' => $unit->nama,
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

        return [
            'actions' => $actions,
            'modules' => $modules,
            'roles' => $roles,
            'units' => $units,
            'users' => $users,
        ];
    }
}
