<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ImportBatch;
use App\Models\Student;
use App\Services\ActivityLogger;
use App\Services\ImportTemplateService;
use App\Services\StudentImportService;
use Illuminate\Http\Request;

class StudentImportController extends Controller
{
    private const MAX_FILE_KB = 5120;

    public function __construct(
        protected StudentImportService $service,
        protected ImportTemplateService $templates,
    ) {}

    public function options(Request $request)
    {
        $this->authorize('import', Student::class);

        $years = AcademicYear::orderByDesc('is_active')->orderByDesc('name')->get();
        $yearId = (int) ($request->query('tahun_ajaran_id') ?: $years->firstWhere('is_active', true)?->id ?: $years->first()?->id);
        $year = $years->firstWhere('id', $yearId);

        return response()->json([
            'years' => $years,
            'active_year_id' => $year?->id,
            'classes' => $year ? $this->classes($year) : [],
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('import', Student::class);

        return ImportBatch::query()
            ->with(['importer:id,name', 'academicYear:id,name'])
            ->orderByDesc('id')
            ->paginate(min(50, max(5, (int) $request->query('per_page', 15))));
    }

    public function template(Request $request)
    {
        $this->authorize('import', Student::class);

        $validated = $request->validate([
            'tahun_ajaran_id' => 'required|integer|exists:academic_years,id',
        ]);

        $year = AcademicYear::findOrFail($validated['tahun_ajaran_id']);
        $path = $this->templates->path($year);

        return response()
            ->download($path, 'template_import_siswa_'.str_replace('/', '-', $year->name).'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function preview(Request $request)
    {
        $this->authorize('import', Student::class);

        $validated = $request->validate([
            'file' => 'required|file|extensions:xlsx,csv|max:'.self::MAX_FILE_KB,
            'tahun_ajaran_id' => 'required|integer|exists:academic_years,id',
            'update_existing' => 'sometimes|boolean',
        ]);

        $batch = $this->service->stage(
            $request->user(),
            $request->file('file'),
            (int) $validated['tahun_ajaran_id'],
            (bool) ($validated['update_existing'] ?? false),
        );

        return response()->json($this->service->previewPayload($batch, $request->only('status', 'per_page')));
    }

    public function show(Request $request, ImportBatch $batch)
    {
        $this->authorize('view', $batch);

        return response()->json($this->service->previewPayload($batch, $request->only('status', 'per_page')));
    }

    public function confirm(Request $request, ImportBatch $batch)
    {
        $this->authorize('confirm', $batch);

        $batch = $this->service->confirm($batch, $request->user());

        return response()->json([
            'message' => 'Import selesai.',
            'batch' => $batch,
            'hasil' => $batch->hasil,
        ]);
    }

    public function errors(ImportBatch $batch)
    {
        $this->authorize('view', $batch);

        return response($this->service->errorCsv($batch), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="error_import_siswa_'.$batch->id.'.csv"',
        ]);
    }

    public function destroy(ImportBatch $batch)
    {
        $this->authorize('delete', $batch);

        if (! $batch->isPreview()) {
            abort(409, 'Batch yang sudah dikonfirmasi tidak dapat dihapus.');
        }

        $batchId = $batch->id;
        $fileName = $batch->file_name;
        $batch->delete();

        ActivityLogger::log('import.cancel', request()->user(), ImportBatch::class, $batchId, [
            'file_name' => $fileName,
        ]);

        return response()->noContent();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function classes(AcademicYear $year): array
    {
        return Classroom::query()
            ->where(function ($query) use ($year) {
                $query->where('tahun_ajaran_id', $year->id)
                    ->orWhere(function ($query) use ($year) {
                        $query->whereNull('tahun_ajaran_id')->where('academic_year', $year->name);
                    });
            })
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'name', 'jalur', 'jenjang', 'grade'])
            ->all();
    }
}
