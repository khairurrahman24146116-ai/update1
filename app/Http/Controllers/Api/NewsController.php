<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function publicIndex(Request $request)
    {
        return News::published()->with('category:id,name,slug')
            ->when($request->search, fn ($q, $v) => $q->where(fn ($qq) => $qq->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")))
            ->latest('published_at')->latest('id')->paginate(12);
    }

    public function publicShow(string $id)
    {
        $news = News::published()->with('category:id,name,slug')->where('id', $id)->orWhere('slug', $id)->firstOrFail();
        if ($news->status !== 'publish') {
            abort(404);
        }

        return $news;
    }

    public function index(Request $request)
    {
        return News::with('category:id,name,slug')
            ->when($request->search, fn ($q, $v) => $q->where(fn ($qq) => $qq->where('title', 'like', "%{$v}%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->latest()->paginate(15);
    }

    public function show(News $news)
    {
        return $news->load('category:id,name,slug');
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:news,slug',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'image' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp|mimetypes:image/jpeg,image/png,image/webp',
            'category_id' => 'nullable|exists:news_categories,id',
            'status' => 'nullable|in:draft,publish',
        ]);
        if (($v['status'] ?? null) === 'publish' && ! $request->user()->hasPermission('content.news.publish')) {
            abort(403, 'Anda tidak memiliki izin memublikasikan berita.');
        }
        $data = collect($v)->except('image')->toArray();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }
        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        $news = News::create($data);
        ActivityLogger::log('news.create', $request->user(), News::class, $news->id, $data);

        return response()->json($news->load('category'), 201);
    }

    public function update(Request $request, News $news)
    {
        $v = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|nullable|string|unique:news,slug,'.$news->id,
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'image' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp',
            'category_id' => 'nullable|exists:news_categories,id',
            'status' => 'nullable|in:draft,publish',
        ]);
        if (($v['status'] ?? null) === 'publish' && ! $request->user()->hasPermission('content.news.publish')) {
            abort(403, 'Anda tidak memiliki izin memublikasikan berita.');
        }
        $data = collect($v)->except('image')->toArray();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }
        $news->update($data);
        ActivityLogger::log('news.update', $request->user(), News::class, $news->id, $data);

        return $news->fresh()->load('category');
    }

    public function destroy(Request $request, News $news)
    {
        $id = $news->id;
        $news->delete();
        ActivityLogger::log('news.delete', $request->user(), News::class, $id);

        return response()->noContent();
    }
}
