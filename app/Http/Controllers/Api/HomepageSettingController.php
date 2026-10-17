<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroMedia;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomepageSettingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get all homepage settings (admin).
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SiteSetting::class);

        $settings = SiteSetting::firstOrCreate(['id' => 1], ['app_name' => 'SMA Madani Al-Aziziyah']);
        $media = HeroMedia::orderBy('sort_order')->get();

        return response()->json([
            'settings' => $settings->only([
                'homepage_badge_text', 'homepage_title', 'homepage_tagline', 'homepage_logo_path',
                'prayer_city_name', 'prayer_latitude', 'prayer_longitude', 'prayer_timezone', 'prayer_card_visible',
                'hero_overlay_opacity', 'logo_path',
            ]),
            'hero_media' => $media,
        ]);
    }

    /**
     * Update homepage text settings (admin).
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

        $validated = $request->validate([
            'homepage_badge_text' => 'nullable|string|max:100',
            'homepage_title' => 'nullable|string|max:200',
            'homepage_tagline' => 'nullable|string|max:300',
            'prayer_city_name' => 'nullable|string|max:100',
            'prayer_latitude' => 'nullable|numeric|between:-90,90',
            'prayer_longitude' => 'nullable|numeric|between:-180,180',
            'prayer_timezone' => 'nullable|string|max:50',
            'prayer_card_visible' => 'boolean',
            'hero_overlay_opacity' => 'integer|between:0,80',
        ]);

        $settings = SiteSetting::firstOrCreate(['id' => 1]);
        $old = $settings->only(array_keys($validated));
        $settings->update($validated);

        ActivityLogger::log('homepage.update', $request->user(), SiteSetting::class, $settings->id, [
            'old' => $old,
            'new' => $validated,
        ]);

        return response()->json(['message' => 'Pengaturan beranda berhasil disimpan.']);
    }

    /**
     * Upload homepage logo (admin).
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            return response()->json(['message' => 'Tipe file tidak diizinkan. Gunakan PNG, JPG, atau WebP.'], 422);
        }

        $settings = SiteSetting::firstOrCreate(['id' => 1]);

        // Delete old file
        if ($settings->homepage_logo_path && Storage::disk('public')->exists($settings->homepage_logo_path)) {
            Storage::disk('public')->delete($settings->homepage_logo_path);
        }

        $extension = $this->extensionFromMime($mime);
        $filename = 'homepage/'.Str::random(32).'.'.$extension;
        $file->storeAs('', $filename, 'public');

        $settings->update(['homepage_logo_path' => $filename]);

        ActivityLogger::log('homepage.upload_logo', $request->user(), SiteSetting::class, $settings->id, [
            'path' => $filename,
        ]);

        return response()->json(['path' => $filename, 'message' => 'Logo beranda berhasil diunggah.']);
    }

    /**
     * Reset homepage logo to default.
     */
    public function resetLogo(Request $request): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

        $settings = SiteSetting::firstOrCreate(['id' => 1]);

        if ($settings->homepage_logo_path && Storage::disk('public')->exists($settings->homepage_logo_path)) {
            Storage::disk('public')->delete($settings->homepage_logo_path);
        }

        $settings->update(['homepage_logo_path' => null]);

        return response()->json(['message' => 'Logo beranda dikembalikan ke default.']);
    }

    /**
     * Upload hero media (image or video).
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

        $request->validate([
            'file' => 'required|file|max:30720',
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();

        $imageTypes = ['image/png', 'image/jpeg', 'image/webp'];
        $videoTypes = ['video/mp4', 'video/webm'];
        $allAllowed = array_merge($imageTypes, $videoTypes);

        if (! in_array($mime, $allAllowed, true)) {
            return response()->json([
                'message' => 'Tipe file tidak diizinkan. Gunakan PNG/JPG/WebP (maks 5 MB) atau MP4/WebM (maks 30 MB).',
            ], 422);
        }

        $isImage = in_array($mime, $imageTypes, true);

        // Image max 5MB, video max 30MB
        $maxBytes = $isImage ? 5 * 1024 * 1024 : 30 * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            $maxLabel = $isImage ? '5 MB' : '30 MB';

            return response()->json([
                'message' => "Ukuran file melebihi batas maksimum {$maxLabel}.",
            ], 422);
        }

        $extension = $this->extensionFromMime($mime);
        $filename = 'hero/'.Str::random(32).'.'.$extension;
        $file->storeAs('', $filename, 'public');

        $maxOrder = HeroMedia::max('sort_order') ?? 0;

        $media = HeroMedia::create([
            'file_path' => $filename,
            'file_type' => $isImage ? 'image' : 'video',
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        ActivityLogger::log('homepage.upload_media', $request->user(), HeroMedia::class, $media->id, [
            'path' => $filename,
            'type' => $media->file_type,
        ]);

        return response()->json([
            'media' => $media,
            'message' => 'Media berhasil diunggah.',
        ]);
    }

    /**
     * Update hero media order and active status.
     */
    public function updateMedia(Request $request): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

        $validated = $request->validate([
            'media' => 'required|array',
            'media.*.id' => 'required|integer|exists:hero_media,id',
            'media.*.sort_order' => 'required|integer|min:0',
            'media.*.is_active' => 'required|boolean',
        ]);

        foreach ($validated['media'] as $item) {
            HeroMedia::where('id', $item['id'])->update([
                'sort_order' => $item['sort_order'],
                'is_active' => $item['is_active'],
            ]);
        }

        return response()->json(['message' => 'Urutan media berhasil diperbarui.']);
    }

    /**
     * Delete hero media.
     */
    public function destroyMedia(Request $request, int $id): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

        $media = HeroMedia::findOrFail($id);

        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        if ($media->poster_path && Storage::disk('public')->exists($media->poster_path)) {
            Storage::disk('public')->delete($media->poster_path);
        }

        ActivityLogger::log('homepage.delete_media', $request->user(), HeroMedia::class, $media->id, [
            'path' => $media->file_path,
        ]);

        $media->delete();

        return response()->json(['message' => 'Media berhasil dihapus.']);
    }

    /**
     * Public endpoint for homepage data.
     */
    public function publicIndex(): JsonResponse
    {
        $settings = SiteSetting::first();
        $media = HeroMedia::where('is_active', true)->orderBy('sort_order')->get();

        return response()->json([
            'homepage_badge_text' => $settings?->homepage_badge_text,
            'homepage_title' => $settings?->homepage_title,
            'homepage_tagline' => $settings?->homepage_tagline,
            'homepage_logo_path' => $settings?->homepage_logo_path,
            'logo_path' => $settings?->logo_path,
            'prayer_city_name' => $settings?->prayer_city_name,
            'prayer_latitude' => $settings?->prayer_latitude ? (float) $settings->prayer_latitude : null,
            'prayer_longitude' => $settings?->prayer_longitude ? (float) $settings->prayer_longitude : null,
            'prayer_timezone' => $settings?->prayer_timezone ?? 'Asia/Jakarta',
            'prayer_card_visible' => $settings?->prayer_card_visible ?? true,
            'hero_overlay_opacity' => $settings?->hero_overlay_opacity ?? 40,
            'hero_media' => $media->map(fn (HeroMedia $m) => [
                'id' => $m->id,
                'url' => $m->file_path,
                'type' => $m->file_type,
                'mime' => $m->mime_type,
                'poster' => $m->poster_path,
            ]),
        ]);
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            default => 'bin',
        };
    }
}
