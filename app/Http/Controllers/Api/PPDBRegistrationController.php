<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePPDBRegistrationRequest;
use App\Models\PPDBRegistration;
use App\Models\PPDBWave;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PPDBRegistrationController extends Controller
{
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
                $data['photo_path'] = $request->file('photo')->store('ppdb/photos', 'public');
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
        return PPDBRegistration::with('wave:id,name,slug')
            ->when($request->wave_id, fn ($q, $v) => $q->where('wave_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->search, fn ($q, $v) => $q->where(fn ($qq) => $qq->where('name', 'like', "%{$v}%")->orWhere('nisn', 'like', "%{$v}%")))
            ->latest()->paginate(15);
    }

    public function show(PPDBRegistration $ppdbRegistration)
    {
        return $ppdbRegistration->load('wave:id,name,slug');
    }

    public function update(Request $request, PPDBRegistration $ppdbRegistration)
    {
        $v = $request->validate(['status' => 'required|in:pending,diterima,ditolak,cadangan', 'notes' => 'nullable|string']);
        $ppdbRegistration->update($v);
        ActivityLogger::log('ppdb_registration.update', $request->user(), PPDBRegistration::class, $ppdbRegistration->id, $v);

        return $ppdbRegistration->fresh()->load('wave');
    }

    public function destroy(Request $request, PPDBRegistration $ppdbRegistration)
    {
        $ppdbRegistration->delete();
        ActivityLogger::log('ppdb_registration.delete', $request->user(), PPDBRegistration::class, $ppdbRegistration->id);

        return response()->noContent();
    }
}
