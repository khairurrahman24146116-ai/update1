<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ServesPrivateFiles;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePPDBRegistrationRequest;
use App\Http\Requests\UpdatePPDBRegistrationRequest;
use App\Models\ActivityLog;
use App\Models\PPDBRegistration;
use App\Models\PPDBWave;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PPDBRegistrationController extends Controller
{
    use ServesPrivateFiles;

    public function publicStore(StorePPDBRegistrationRequest $request)
    {
        $v = $request->validated();
        $wave = PPDBWave::findOrFail($v['wave_id']);
        if (! $wave->is_public || $wave->status !== 'buka') {
            return response()->json(['message' => 'Gelombang tidak tersedia untuk pendaftaran'], 422);
        }
        if ($wave->start_date && now()->lt($wave->start_date)) {
            return response()->json(['message' => 'Pendaftaran belum dibuka'], 422);
        }
        if ($wave->end_date && now()->gt($wave->end_date)) {
            return response()->json(['message' => 'Pendaftaran sudah ditutup'], 422);
        }

        $data = collect($v)->except(['photo', 'consent'])->toArray();
        $data['status'] = 'pending';
        $data['declaration_date'] = now()->toDateString();
        $data['consent_at'] = now();

        $reg = DB::transaction(function () use ($request, $data, $wave) {
            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('ppdb/photos', 'local');
            }
            $data['registration_number'] = $this->generateNumber($wave);
            $model = new PPDBRegistration;
            foreach ($data as $k => $val) {
                if ($k === 'medical_history' && is_array($val)) {
                    $model->{$k} = $val;
                } else {
                    $model->{$k} = $val;
                }
            }
            $model->save();

            return $model;
        });

        ActivityLogger::logGuest('ppdb_registration.create', [
            'registration_number' => $reg->registration_number,
            'wave_id' => $wave->id,
            'wave_name' => $wave->name,
            'status' => $reg->status,
        ]);

        return response()->json([
            'registration_number' => $reg->registration_number,
            'name' => $reg->name,
            'jenjang' => $reg->jenjang,
            'wave' => ['id' => $wave->id, 'name' => $wave->name, 'slug' => $wave->slug],
            'registered_at' => $reg->created_at,
            'status' => $reg->status,
        ], 201);
    }

    private function generateNumber(PPDBWave $wave): string
    {
        $year = now()->format('Y');
        $prefix = 'PPDB-'.$year.'-'.str_pad((string) $wave->id, 3, '0', STR_PAD_LEFT);
        $seq = PPDBRegistration::where('wave_id', $wave->id)->count() + 1;
        $number = $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        while (PPDBRegistration::where('registration_number', $number)->exists()) {
            $seq++;
            $number = $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'wave_id' => 'nullable|integer|exists:p_p_d_b_waves,id',
            'status' => 'nullable|in:pending,diterima,ditolak,cadangan',
            'jenjang' => 'nullable|in:lanjut,sma,smp,salafiyah',
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return PPDBRegistration::with('wave:id,name,slug')
            ->when($filters['wave_id'] ?? null, fn ($q, $v) => $q->where('wave_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['jenjang'] ?? null, fn ($q, $v) => $q->where('jenjang', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v, 'Asia/Jakarta')->startOfDay()->utc()))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v, 'Asia/Jakarta')->endOfDay()->utc()))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$v}%")
                ->orWhere('nisn', 'like', "%{$v}%")
                ->orWhere('registration_number', 'like', "%{$v}%")))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function stats(Request $request)
    {
        $filters = $request->validate([
            'wave_id' => 'nullable|integer|exists:p_p_d_b_waves,id',
        ]);

        $base = PPDBRegistration::query()
            ->when($filters['wave_id'] ?? null, fn ($q, $v) => $q->where('wave_id', $v));

        $todayStart = now()->timezone('Asia/Jakarta')->startOfDay()->utc();
        $todayEnd = now()->timezone('Asia/Jakarta')->endOfDay()->utc();

        $byJenjang = (clone $base)
            ->select('jenjang', DB::raw('count(*) as total'))
            ->groupBy('jenjang')
            ->pluck('total', 'jenjang');

        $byStatus = (clone $base)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $openWave = PPDBWave::where('is_public', true)
            ->where('status', 'buka')
            ->orderByDesc('start_date')
            ->get()
            ->first(fn (PPDBWave $wave) => $this->isWaveOpen($wave));

        return response()->json([
            'total' => (clone $base)->count(),
            'today' => (clone $base)->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'by_jenjang' => collect(PPDBRegistration::JENJANG_LABELS)
                ->keys()
                ->mapWithKeys(fn ($key) => [$key => (int) ($byJenjang[$key] ?? 0)]),
            'by_status' => collect(['pending', 'diterima', 'cadangan', 'ditolak'])
                ->mapWithKeys(fn ($key) => [$key => (int) ($byStatus[$key] ?? 0)]),
            'by_wave' => PPDBWave::withCount('registrations')
                ->orderByDesc('id')
                ->get(['id', 'name', 'status', 'start_date', 'end_date', 'is_public']),
            'open_wave' => $openWave ? [
                'id' => $openWave->id,
                'name' => $openWave->name,
                'start_date' => $openWave->start_date,
                'end_date' => $openWave->end_date,
            ] : null,
        ]);
    }

    public function publicStats()
    {
        $wave = PPDBWave::where('is_public', true)
            ->where('status', 'buka')
            ->orderByDesc('start_date')
            ->get()
            ->first(fn (PPDBWave $candidate) => $this->isWaveOpen($candidate))
            ?? PPDBWave::where('is_public', true)->orderByDesc('start_date')->first();

        $total = $wave
            ? PPDBRegistration::where('wave_id', $wave->id)->count()
            : PPDBRegistration::count();

        return response()->json([
            'total' => $total,
            'wave' => $wave ? [
                'id' => $wave->id,
                'name' => $wave->name,
                'status' => $wave->status,
                'start_date' => $wave->start_date,
                'end_date' => $wave->end_date,
                'is_open' => $this->isWaveOpen($wave),
            ] : null,
        ]);
    }

    public function publicCheckStatus(Request $request)
    {
        $v = $request->validate([
            'registration_number' => 'required_without:nik|nullable|string|max:50',
            'nik' => 'required_without:registration_number|nullable|string|size:16|regex:/^[0-9]+$/',
        ]);

        $registration = PPDBRegistration::with('wave:id,name,slug')
            ->when($v['registration_number'] ?? null, fn ($q, $value) => $q->where('registration_number', trim($value)))
            ->when($v['nik'] ?? null, fn ($q, $value) => $q->where('nik', $value))
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages([
                'registration_number' => 'Data pendaftaran tidak ditemukan. Periksa kembali nomor pendaftaran atau NIK.',
            ]);
        }

        return response()->json([
            'registration_number' => $registration->registration_number,
            'name' => $registration->name,
            'jenjang' => $registration->jenjang,
            'jenjang_label' => $registration->jenjangLabel(),
            'wave' => $registration->wave ? [
                'id' => $registration->wave->id,
                'name' => $registration->wave->name,
                'slug' => $registration->wave->slug,
            ] : null,
            'status' => $registration->status,
            'status_label' => $registration->statusLabel(),
            'status_message' => $this->statusMessage($registration->status),
            'registered_at' => $registration->created_at,
            'decided_at' => $registration->updated_at,
        ]);
    }

    public function publicAnnouncements(Request $request)
    {
        $filters = $request->validate([
            'wave_id' => 'nullable|integer|exists:p_p_d_b_waves,id',
            'jenjang' => 'nullable|in:lanjut,sma,smp,salafiyah',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $paginator = PPDBRegistration::query()
            ->where('status', 'diterima')
            ->with('wave:id,name,slug')
            ->when($filters['wave_id'] ?? null, fn ($q, $value) => $q->where('wave_id', $value))
            ->when($filters['jenjang'] ?? null, fn ($q, $value) => $q->where('jenjang', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$value}%")
                ->orWhere('registration_number', 'like', "%{$value}%")))
            ->orderBy('registration_number')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (PPDBRegistration $registration) => [
                'registration_number' => $registration->registration_number,
                'name' => $registration->name,
                'jenjang' => $registration->jenjang,
                'jenjang_label' => $registration->jenjangLabel(),
                'wave' => $registration->wave ? [
                    'id' => $registration->wave->id,
                    'name' => $registration->wave->name,
                    'slug' => $registration->wave->slug,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'waves' => PPDBWave::query()
                ->whereHas('registrations', fn ($q) => $q->where('status', 'diterima'))
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'jenjang_labels' => PPDBRegistration::JENJANG_LABELS,
        ]);
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            'diterima' => 'Selamat! Pendaftar dinyatakan DITERIMA. Silakan menunggu informasi daftar ulang dari panitia.',
            'cadangan' => 'Pendaftar berada di daftar CADANGAN. Panitia akan menghubungi jika tersedia kuota.',
            'ditolak' => 'Mohon maaf, pendaftar belum dapat diterima pada gelombang ini.',
            default => 'Pendaftaran sedang menunggu verifikasi panitia.',
        };
    }

    private function isWaveOpen(PPDBWave $wave): bool
    {
        if ($wave->status !== 'buka') {
            return false;
        }
        if ($wave->start_date && now()->lt($wave->start_date)) {
            return false;
        }
        if ($wave->end_date && now()->gt($wave->end_date)) {
            return false;
        }

        return true;
    }

    public function show(PPDBRegistration $ppdbRegistration)
    {
        $ppdbRegistration->load('wave:id,name,slug');

        return $this->withLastEdit($ppdbRegistration);
    }

    public function photo(PPDBRegistration $ppdbRegistration)
    {
        return $this->privateFileResponse($ppdbRegistration->photo_path);
    }

    public function publicPhotoUpload(Request $request)
    {
        $v = $request->validate([
            'registration_number' => 'required|string|max:50',
            'photo' => 'required|file|max:2048|mimes:jpg,jpeg,png,webp',
        ]);

        $registration = PPDBRegistration::where('registration_number', $v['registration_number'])->first();

        if (! $registration) {
            throw ValidationException::withMessages([
                'registration_number' => 'Nomor pendaftaran tidak ditemukan.',
            ]);
        }

        $registration->load('wave');

        if (! $registration->wave || ! $this->isWaveOpen($registration->wave)) {
            throw ValidationException::withMessages([
                'registration_number' => 'Gelombang pendaftaran sudah ditutup, foto tidak dapat diunggah.',
            ]);
        }

        $path = $request->file('photo')->store('ppdb/photos', 'local');

        if ($registration->photo_path) {
            Storage::disk('local')->delete($registration->photo_path);
        }

        $registration->update(['photo_path' => $path]);

        ActivityLogger::logGuest('ppdb_registration.photo_upload', [
            'registration_number' => $registration->registration_number,
            'wave_id' => $registration->wave_id,
        ]);

        return response()->json([
            'message' => 'Pas foto berhasil diunggah.',
            'registration_number' => $registration->registration_number,
            'name' => $registration->name,
            'jenjang' => $registration->jenjang,
        ]);
    }

    public function uploadPhoto(Request $request, PPDBRegistration $ppdbRegistration)
    {
        $request->validate([
            'photo' => 'required|file|max:2048|mimes:jpg,jpeg,png,webp',
        ]);

        $path = $request->file('photo')->store('ppdb/photos', 'local');

        if ($ppdbRegistration->photo_path) {
            Storage::disk('local')->delete($ppdbRegistration->photo_path);
        }

        $ppdbRegistration->update(['photo_path' => $path]);

        ActivityLogger::log('ppdb_registration.photo_upload', $request->user(), PPDBRegistration::class, $ppdbRegistration->id);

        return $ppdbRegistration->fresh()->load('wave');
    }

    public function pdf(Request $request, PPDBRegistration $ppdbRegistration)
    {
        $startedAt = microtime(true);
        $ppdbRegistration->load('wave:id,name,slug');
        $printedAt = now()->timezone('Asia/Jakarta');

        ActivityLogger::log('ppdb_registration.pdf', $request->user(), PPDBRegistration::class, $ppdbRegistration->id, [
            'registration_number' => $ppdbRegistration->registration_number,
        ]);

        $response = Pdf::loadView('reports.ppdb-registration-pdf', [
            'registration' => $ppdbRegistration,
            'printedAt' => $printedAt,
            'printedBy' => $request->user(),
            'photoDataUri' => $this->pdfPhotoDataUri($ppdbRegistration),
        ])->setPaper('a4', 'portrait')
            ->download('formulir-ppdb-'.($ppdbRegistration->registration_number ?: $ppdbRegistration->id).'.pdf');

        Log::info('ppdb_registration.pdf rendered', [
            'registration_id' => $ppdbRegistration->id,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }

    public function pdfRecap(Request $request)
    {
        $startedAt = microtime(true);
        $filters = $request->validate([
            'wave_id' => 'nullable|integer|exists:p_p_d_b_waves,id',
            'status' => 'nullable|in:pending,diterima,ditolak,cadangan',
            'jenjang' => 'nullable|in:lanjut,sma,smp,salafiyah',
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = PPDBRegistration::with('wave:id,name,slug')
            ->when($filters['wave_id'] ?? null, fn ($q, $v) => $q->where('wave_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['jenjang'] ?? null, fn ($q, $v) => $q->where('jenjang', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v, 'Asia/Jakarta')->startOfDay()->utc()))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v, 'Asia/Jakarta')->endOfDay()->utc()))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($qq) => $qq->where('name', 'like', "%{$v}%")->orWhere('nisn', 'like', "%{$v}%")->orWhere('registration_number', 'like', "%{$v}%")))
            ->orderBy('registration_number');

        $limit = (int) config('ppdb.pdf_recap_max_rows', 300);
        $count = (clone $query)->count();
        if ($count > $limit) {
            throw ValidationException::withMessages([
                'report' => "Data terlalu banyak untuk dicetak ({$count} pendaftar). Maksimal {$limit} pendaftar per PDF; persempit filter atau ekspor data.",
            ]);
        }

        $rows = $query->get();
        $printedAt = now()->timezone('Asia/Jakarta');
        $waveName = isset($filters['wave_id']) ? PPDBWave::find($filters['wave_id'])?->name : null;

        ActivityLogger::log('ppdb_registration.pdf_recap', $request->user(), PPDBRegistration::class, null, [
            'filters' => $filters,
            'rows' => $rows->count(),
        ]);

        $response = Pdf::loadView('reports.ppdb-registration-recap-pdf', [
            'registrations' => $rows,
            'filters' => $filters,
            'waveName' => $waveName,
            'printedAt' => $printedAt,
            'printedBy' => $request->user(),
        ])->setPaper('a4', 'landscape')
            ->download('rekap-ppdb-'.$printedAt->format('Ymd-His').'.pdf');

        Log::info('ppdb_registration.pdf_recap rendered', [
            'rows' => $rows->count(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }

    private function pdfPhotoDataUri(PPDBRegistration $registration): ?string
    {
        try {
            $sourcePath = $this->photoPath($registration);
            if ($sourcePath === null) {
                return null;
            }

            $derivativePath = $this->ensurePdfPhotoDerivative($sourcePath, $registration->photo_path);
            $path = $derivativePath ?? $sourcePath;
            $contents = @file_get_contents($path);
            if ($contents === false) {
                return null;
            }

            $mime = strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        } catch (\Throwable $e) {
            Log::warning('ppdb_registration.pdf photo fallback', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function photoPath(PPDBRegistration $registration): ?string
    {
        if (! $registration->photo_path || ! Storage::disk('local')->exists($registration->photo_path)) {
            return null;
        }

        return Storage::disk('local')->path($registration->photo_path);
    }

    private function ensurePdfPhotoDerivative(string $sourcePath, string $relativePath): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $derivativeRelative = 'ppdb/photos_pdf/'.pathinfo($relativePath, PATHINFO_FILENAME).'.jpg';
        $disk = Storage::disk('local');
        $derivativePath = $disk->path($derivativeRelative);

        $sourceMtime = @filemtime($sourcePath) ?: 0;
        $derivativeMtime = @filemtime($derivativePath) ?: 0;
        if ($disk->exists($derivativeRelative) && $derivativeMtime >= $sourceMtime) {
            return $derivativePath;
        }

        $directory = dirname($derivativePath);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return null;
        }

        [$width, $height, $type] = $info;
        if ($width < 1 || $height < 1) {
            return null;
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };
        if ($source === false) {
            return null;
        }

        $maxWidth = 400;
        $targetWidth = $width > $maxWidth ? $maxWidth : $width;
        $targetHeight = (int) max(1, round($height * $targetWidth / $width));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $saved = @imagejpeg($canvas, $derivativePath, 80);
        imagedestroy($source);
        imagedestroy($canvas);

        return $saved ? $derivativePath : null;
    }

    public function update(UpdatePPDBRegistrationRequest $request, PPDBRegistration $ppdbRegistration)
    {
        $data = $request->validated();

        $effectiveJenjang = $data['jenjang'] ?? $ppdbRegistration->jenjang;
        if ($effectiveJenjang === 'salafiyah') {
            $data['gender'] = 'P';
        }

        $ppdbRegistration->fill($data);

        $changes = [];
        foreach ($ppdbRegistration->getDirty() as $field => $newValue) {
            $oldValue = $ppdbRegistration->getOriginal($field);
            $changes[$field] = [
                'old' => $this->formatAuditValue($oldValue),
                'new' => $this->formatAuditValue($newValue),
            ];
        }

        if (! empty($changes)) {
            $ppdbRegistration->save();

            $dataOnly = array_diff(array_keys($data), ['status', 'notes']);
            $action = count($dataOnly) > 0 ? 'ppdb_registration.edit' : 'ppdb_registration.update';

            ActivityLogger::log($action, $request->user(), PPDBRegistration::class, $ppdbRegistration->id, [
                'changes' => $changes,
                'status' => $ppdbRegistration->status,
            ]);
        }

        return $this->withLastEdit($ppdbRegistration->fresh()->load('wave:id,name,slug'));
    }

    private function withLastEdit(PPDBRegistration $registration): PPDBRegistration
    {
        $log = ActivityLog::query()
            ->where('action', 'ppdb_registration.edit')
            ->where('target_type', PPDBRegistration::class)
            ->where('target_id', $registration->id)
            ->latest('id')
            ->first(['user_name', 'created_at']);

        $registration->setAttribute('last_edited_by', $log?->user_name);
        $registration->setAttribute('last_edited_at', $log?->created_at?->toIso8601String());

        return $registration;
    }

    private function formatAuditValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            return $value;
        }

        return $value;
    }

    public function destroy(Request $request, PPDBRegistration $ppdbRegistration)
    {
        $ppdbRegistration->delete();
        ActivityLogger::log('ppdb_registration.delete', $request->user(), PPDBRegistration::class, $ppdbRegistration->id);

        return response()->noContent();
    }
}
