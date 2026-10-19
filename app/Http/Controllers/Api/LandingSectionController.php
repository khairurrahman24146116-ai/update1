<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingSection;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class LandingSectionController extends Controller
{
    public function index()
    {
        return LandingSection::orderBy('order')->get();
    }

    public function store(Request $request)
    {
        if (! $request->user()?->hasPermission('settings.landing.manage')) {
            abort(403);
        }
        $v = $request->validate(['key' => 'required|string|unique:landing_sections,key', 'title' => 'required|string', 'body' => 'nullable|string', 'image_path' => 'nullable|string', 'order' => 'nullable|integer', 'is_visible' => 'boolean']);
        $item = LandingSection::create($v);
        ActivityLogger::log('landing.create', $request->user(), LandingSection::class, $item->id, $v);

        return response()->json($item, 201);
    }

    public function update(Request $request, LandingSection $landingSection)
    {
        if (! $request->user()?->hasPermission('settings.landing.manage')) {
            abort(403);
        }
        $v = $request->validate(['title' => 'sometimes|required|string', 'body' => 'nullable|string', 'image_path' => 'nullable|string', 'order' => 'nullable|integer', 'is_visible' => 'boolean']);
        $old = $landingSection->toArray();
        $landingSection->update($v);
        ActivityLogger::log('landing.update', $request->user(), LandingSection::class, $landingSection->id, ['old' => $old, 'new' => $v]);

        return $landingSection;
    }

    public function destroy(Request $request, LandingSection $landingSection)
    {
        if (! $request->user()?->hasPermission('settings.landing.manage')) {
            abort(403);
        }
        $id = $landingSection->id;
        $landingSection->delete();
        ActivityLogger::log('landing.delete', $request->user(), LandingSection::class, $id, ['id' => $id]);

        return response()->noContent();
    }
}
