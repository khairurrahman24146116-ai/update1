<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', SiteSetting::class);

        return SiteSetting::firstOrCreate(['id' => 1], ['app_name' => 'SMA Madani Al-Aziziyah']);
    }

    public function update(Request $request)
    {
        $this->authorize('update', SiteSetting::class);
        $data = SiteSetting::firstOrCreate(['id' => 1]);
        $old = $data->toArray();
        $v = $request->validate([
            'app_name' => 'required|string', 'nspp' => 'nullable|string', 'npsn' => 'nullable|string',
            'jenjang' => 'nullable|string', 'akreditasi' => 'nullable|string', 'milad' => 'nullable|string',
            'tagline' => 'nullable|string', 'vision' => 'nullable|string', 'address' => 'nullable|string',
            'logo_path' => 'nullable|string', 'favicon' => 'nullable|string', 'kop_surat' => 'nullable|string', 'hero_image' => 'nullable|string',
            'phone' => 'nullable|string', 'pstn' => 'nullable|string', 'wa_helpdesk' => 'nullable|string', 'email' => 'nullable|email',
            'map_lat' => 'nullable|string', 'map_lng' => 'nullable|string',
            'instagram' => 'nullable|string', 'youtube' => 'nullable|string', 'tiktok' => 'nullable|string', 'facebook' => 'nullable|string',
        ]);
        $data->update($v);
        ActivityLogger::log('settings.update', $request->user(), SiteSetting::class, $data->id, ['old' => $old, 'new' => $v]);

        return $data;
    }

    public function upload(Request $request)
    {
        $this->authorize('update', SiteSetting::class);
        $request->validate([
            'file' => 'required|file|max:2048|mimes:jpg,jpeg,png,webp|mimetypes:image/jpeg,image/png,image/webp',
            'field' => 'required|in:logo_path,favicon,kop_surat,hero_image,logo,favicon_file',
        ]);
        $field = $request->field === 'logo' ? 'logo_path' : ($request->field === 'favicon_file' ? 'favicon' : $request->field);
        $path = $request->file('file')->store('site', 'public');
        $data = SiteSetting::firstOrCreate(['id' => 1]);
        $data->update([$field => $path]);
        ActivityLogger::log('settings.upload', $request->user(), SiteSetting::class, $data->id, ['field' => $field, 'path' => $path]);

        return response()->json(['path' => $path]);
    }

    public function publicIndex()
    {
        return SiteSetting::first();
    }
}
