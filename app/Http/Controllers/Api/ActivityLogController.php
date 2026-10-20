<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use App\Services\ActivityLogService;
use App\Support\ActivityLogCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', ActivityLog::class);
        $filters = $this->filters($request);
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        return $this->service->filtered($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ActivityLog $log): array => $this->present($log));
    }

    public function options()
    {
        $this->authorize('viewAny', ActivityLog::class);

        return response()->json($this->service->options());
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $this->authorize('view', $activityLog);

        return response()->json($this->present($activityLog));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ActivityLog::class);
        $filters = $this->filters($request);
        $maxId = (int) (ActivityLog::query()->max('id') ?? 0);
        $query = $this->service->filtered($filters)->where('id', '<=', $maxId);
        $count = (clone $query)->count();

        ActivityLogger::log('activity_log.export', $request->user(), null, null, [
            'filters' => $filters,
            'rows' => $count,
        ]);

        $filename = 'log-aktivitas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Waktu', 'Pengguna', 'Role', 'Modul', 'Aksi', 'Deskripsi', 'Target', 'IP', 'Metadata'], ',', '"', '');

            $query->chunk(500, function ($logs) use ($out): void {
                foreach ($logs as $log) {
                    $metadata = is_array($log->metadata)
                        ? json_encode(ActivityLogCatalog::sanitizeMetadata($log->metadata), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : '';

                    fputcsv($out, array_map([$this, 'csvValue'], [
                        $log->created_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        $log->user_name ?: 'Sistem',
                        $log->user_role ?: '-',
                        ActivityLogCatalog::moduleLabel($log->module),
                        $log->action,
                        $log->description,
                        $log->target_type ? Str::afterLast($log->target_type, '\\').'#'.$log->target_id : '-',
                        $log->ip_address ?: '-',
                        Str::limit((string) $metadata, 1000, ''),
                    ]), ',', '"', '');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request)
    {
        $this->authorize('print', ActivityLog::class);
        $filters = $this->filters($request);
        $maxId = (int) (ActivityLog::query()->max('id') ?? 0);
        $query = $this->service->filtered($filters)->where('id', '<=', $maxId);
        $limit = (int) config('activity-log.pdf_max_rows', 2000);
        $count = (clone $query)->count();

        if ($count > $limit) {
            throw ValidationException::withMessages([
                'report' => "Data terlalu banyak untuk dicetak ({$count} baris). Maksimal {$limit} baris; persempit filter atau gunakan CSV.",
            ]);
        }

        $logs = $query->get();
        $printedAt = now()->timezone('Asia/Jakarta');

        ActivityLogger::log('activity_log.pdf', $request->user(), null, null, [
            'filters' => $filters,
            'rows' => $logs->count(),
        ]);

        return Pdf::loadView('reports.activity-logs-pdf', [
            'logs' => $logs,
            'filters' => $filters,
            'printedAt' => $printedAt,
            'printedBy' => $request->user(),
            'appName' => SiteSetting::query()->first()?->app_name ?: config('app.name'),
        ])->setPaper('a4', 'landscape')->download('log-aktivitas-'.$printedAt->format('Ymd-His').'.pdf');
    }

    public function retention()
    {
        $this->authorize('manageRetention', ActivityLog::class);

        return response()->json($this->service->retentionPreview());
    }

    public function retentionPreview(Request $request)
    {
        $this->authorize('manageRetention', ActivityLog::class);
        $data = $request->validate(['months' => 'nullable|integer|min:1|max:120']);

        return response()->json($this->service->retentionPreview(isset($data['months']) ? (int) $data['months'] : null));
    }

    public function updateRetention(Request $request)
    {
        $this->authorize('manageRetention', ActivityLog::class);
        $data = $request->validate(['months' => 'required|integer|min:1|max:120']);
        $old = $this->service->retentionMonths();
        $months = $this->service->updateRetention((int) $data['months']);

        ActivityLogger::log('activity_log.retention.update', $request->user(), null, null, [
            'old_months' => $old,
            'new_months' => $months,
        ]);

        return response()->json($this->service->retentionPreview($months));
    }

    public function prune(Request $request)
    {
        $this->authorize('prune', ActivityLog::class);
        $data = $request->validate([
            'confirm' => 'required|accepted',
            'months' => 'nullable|integer|min:1|max:120',
        ]);

        $months = $this->service->clampMonths((int) ($data['months'] ?? $this->service->retentionMonths()));
        $cutoff = now()->subMonths($months)->startOfDay();
        $deleted = $this->service->prune($months);

        ActivityLogger::log('activity_log.prune', $request->user(), null, null, [
            'months' => $months,
            'cutoff' => $cutoff->toDateString(),
            'deleted' => $deleted,
        ]);

        return response()->json([
            'message' => "{$deleted} log lebih lama dari {$cutoff->toDateString()} dihapus.",
            'deleted' => $deleted,
            'months' => $months,
            'cutoff' => $cutoff->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:50',
            'action' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer|min:1',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return collect($data)
            ->reject(fn ($value): bool => $value === null || $value === '')
            ->map(fn ($value, string $key) => $key === 'user_id' ? (int) $value : $value)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ActivityLog $log): array
    {
        $metadata = is_array($log->metadata)
            ? ActivityLogCatalog::sanitizeMetadata($log->metadata)
            : null;

        return [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name ?: 'Sistem',
            'user_role' => $log->user_role,
            'action' => $log->action,
            'action_label' => ActivityLogCatalog::descriptionFor($log->action),
            'module' => $log->module ?: ActivityLogCatalog::moduleFor($log->action),
            'module_label' => ActivityLogCatalog::moduleLabel($log->module),
            'description' => $log->description ?: ActivityLogCatalog::descriptionFor($log->action),
            'target_type' => $log->target_type,
            'target_id' => $log->target_id,
            'target_label' => $log->target_type ? Str::afterLast($log->target_type, '\\').'#'.$log->target_id : null,
            'metadata' => $metadata,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at?->toIso8601String(),
            'created_at_wib' => $log->created_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
