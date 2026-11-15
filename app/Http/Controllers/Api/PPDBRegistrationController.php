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
        $ppdbRegistration->load('wave:id,name,slug');
        $printedAt = now()->timezone('Asia/Jakarta');

        ActivityLogger::log('ppdb_registration.pdf', $request->user(), PPDBRegistration::class, $ppdbRegistration->id, [
            'registration_number' => $ppdbRegistration->registration_number,
        ]);

        return Pdf::loadView('reports.ppdb-registration-pdf', [
            'registration' => $ppdbRegistration,
            'printedAt' => $printedAt,
            'printedBy' => $request->user(),
            'photoPath' => $this->photoPath($ppdbRegistration),
        ])->setPaper('a4', 'portrait')
            ->download('formulir-ppdb-'.($ppdbRegistration->registration_number ?: $ppdbRegistration->id).'.pdf');
    }

    public function pdfRecap(Request $request)
    {
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

        $limit = 1000;
        $count = (clone $query)->count();
        if ($count > $limit) {
            throw ValidationException::withMessages([
                'report' => "Data terlalu banyak untuk dicetak ({$count} pendaftar). Maksimal {$limit} pendaftar; persempit filter terlebih dahulu.",
            ]);
        }

        $rows = $query->get();
        $printedAt = now()->timezone('Asia/Jakarta');
        $waveName = isset($filters['wave_id']) ? PPDBWave::find($filters['wave_id'])?->name : null;

        ActivityLogger::log('ppdb_registration.pdf_recap', $request->user(), PPDBRegistration::class, null, [
            'filters' => $filters,
            'rows' => $rows->count(),
        ]);

        return Pdf::loadView('reports.ppdb-registration-recap-pdf', [
            'registrations' => $rows,
            'filters' => $filters,
            'waveName' => $waveName,
            'printedAt' => $printedAt,
            'printedBy' => $request->user(),
        ])->setPaper('a4', 'landscape')
            ->download('rekap-ppdb-'.$printedAt->format('Ymd-His').'.pdf');
    }

    private function photoPath(PPDBRegistration $registration): ?string
    {
        if (! $registration->photo_path || ! Storage::disk('local')->exists($registration->photo_path)) {
            return null;
        }

        return Storage::disk('local')->path($registration->photo_path);
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
