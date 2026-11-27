<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PPDBWave;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PPDBWaveController extends Controller
{
    public function publicIndex()
    {
        return PPDBWave::where('is_public', true)->orderBy('start_date')->get();
    }

    public function publicShow(string $id)
    {
        $w = PPDBWave::where('is_public', true)->where('id', $id)->orWhere('slug', $id)->first();
        if (! $w) {
            abort(404);
        }

        return $w;
    }

    public function index(Request $request)
    {
        return PPDBWave::orderByDesc('id')->paginate(15);
    }

    public function show(PPDBWave $ppdbWave)
    {
        return $ppdbWave->loadCount('registrations');
    }

    public function store(Request $request)
    {
        $v = $request->validate(['name' => 'required|string|max:255', 'slug' => 'nullable|string|unique:p_p_d_b_waves,slug', 'description' => 'nullable|string', 'banner' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date', 'schedule_days' => 'nullable|string|max:120', 'schedule_hours' => 'nullable|string|max:120', 'status' => 'nullable|in:buka,tutup,archived', 'is_public' => 'boolean']);
        $data = collect($v)->except('banner')->toArray();
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('ppdb', 'public');
        }
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $w = PPDBWave::create($data);
        ActivityLogger::log('ppdb_wave.create', $request->user(), PPDBWave::class, $w->id, $data);

        return response()->json($w, 201);
    }

    public function update(Request $request, PPDBWave $ppdbWave)
    {
        $v = $request->validate(['name' => 'sometimes|string|max:255', 'slug' => 'sometimes|nullable|string|unique:p_p_d_b_waves,slug,'.$ppdbWave->id, 'description' => 'nullable|string', 'banner' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date', 'schedule_days' => 'nullable|string|max:120', 'schedule_hours' => 'nullable|string|max:120', 'status' => 'sometimes|in:buka,tutup,archived', 'is_public' => 'boolean']);
        $data = collect($v)->except('banner')->toArray();
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('ppdb', 'public');
        }
        $ppdbWave->update($data);
        ActivityLogger::log('ppdb_wave.update', $request->user(), PPDBWave::class, $ppdbWave->id, $data);

        return $ppdbWave->fresh();
    }

    public function destroy(Request $request, PPDBWave $ppdbWave)
    {
        if ($ppdbWave->registrations()->exists()) {
            abort(409, 'Gelombang masih memiliki pendaftar');
        }
        $id = $ppdbWave->id;
        $ppdbWave->delete();
        ActivityLogger::log('ppdb_wave.delete', $request->user(), PPDBWave::class, $id);

        return response()->noContent();
    }
}
