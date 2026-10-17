<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrganizationMember;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationMemberController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all structure members for the admin panel.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', OrganizationMember::class);

        return response()->json([
            'members' => OrganizationMember::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * Public hierarchical structure (active members only).
     */
    public function publicIndex(): JsonResponse
    {
        $members = OrganizationMember::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'members' => $members->map(fn (OrganizationMember $m) => [
                'id' => $m->id,
                'parent_id' => $m->parent_id,
                'name' => $m->name,
                'position' => $m->position,
                'photo' => $m->photo_path,
                'sort_order' => $m->sort_order,
                'is_confirmed' => $m->is_confirmed,
            ])->values(),
        ]);
    }

    /**
     * Store a new structure member.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', OrganizationMember::class);

        $validated = $this->validatePayload($request);

        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = ((int) OrganizationMember::where('parent_id', $validated['parent_id'] ?? null)->max('sort_order')) + 1;
        }

        $member = OrganizationMember::create($validated);

        ActivityLogger::log('organization.create', $request->user(), OrganizationMember::class, $member->id, $validated);

        return response()->json([
            'member' => $member,
            'message' => 'Anggota struktur berhasil ditambahkan.',
        ], 201);
    }

    /**
     * Update an existing structure member.
     */
    public function update(Request $request, OrganizationMember $organizationMember): JsonResponse
    {
        $this->authorize('update', $organizationMember);

        $validated = $this->validatePayload($request, $organizationMember);

        if (isset($validated['parent_id']) && $validated['parent_id'] !== null
            && $this->wouldCreateCycle($organizationMember->id, (int) $validated['parent_id'])) {
            return response()->json([
                'message' => 'Atasan tidak valid: hubungan tersebut akan membentuk siklus hierarki.',
            ], 422);
        }

        $old = $organizationMember->only(array_keys($validated));
        $organizationMember->update($validated);

        ActivityLogger::log('organization.update', $request->user(), OrganizationMember::class, $organizationMember->id, [
            'old' => $old,
            'new' => $validated,
        ]);

        return response()->json([
            'member' => $organizationMember->fresh(),
            'message' => 'Anggota struktur berhasil diperbarui.',
        ]);
    }

    /**
     * Delete a structure member, reparenting its children to its parent.
     */
    public function destroy(Request $request, OrganizationMember $organizationMember): JsonResponse
    {
        $this->authorize('delete', $organizationMember);

        if ($organizationMember->photo_path && ! Str::startsWith($organizationMember->photo_path, '/')) {
            if (Storage::disk('public')->exists($organizationMember->photo_path)) {
                Storage::disk('public')->delete($organizationMember->photo_path);
            }
        }

        OrganizationMember::where('parent_id', $organizationMember->id)
            ->update(['parent_id' => $organizationMember->parent_id]);

        $id = $organizationMember->id;
        $organizationMember->delete();

        ActivityLogger::log('organization.delete', $request->user(), OrganizationMember::class, $id, ['id' => $id]);

        return response()->json(['message' => 'Anggota struktur berhasil dihapus.']);
    }

    /**
     * Upload a member photo (admin).
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $this->authorize('create', OrganizationMember::class);

        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            return response()->json(['message' => 'Tipe file tidak diizinkan. Gunakan PNG, JPG, atau WebP.'], 422);
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'bin',
        };

        $filename = 'organization/'.Str::random(32).'.'.$extension;
        $file->storeAs('', $filename, 'public');

        ActivityLogger::log('organization.upload_photo', $request->user(), OrganizationMember::class, null, [
            'path' => $filename,
        ]);

        return response()->json([
            'path' => $filename,
            'message' => 'Foto berhasil diunggah.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?OrganizationMember $member = null): array
    {
        $rules = [
            'parent_id' => ['nullable', 'integer', 'exists:organization_members,id'],
            'name' => ['nullable', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_confirmed' => ['boolean'],
            'is_active' => ['boolean'],
        ];

        if ($member !== null) {
            $rules['parent_id'][] = Rule::notIn([$member->id]);
        }

        return $request->validate($rules);
    }

    /**
     * Prevent assigning a descendant as the parent of a member.
     */
    private function wouldCreateCycle(int $memberId, int $parentId): bool
    {
        $current = OrganizationMember::find($parentId);
        $guard = 0;

        while ($current !== null && $guard < 100) {
            if ($current->id === $memberId) {
                return true;
            }

            $current = $current->parent_id ? OrganizationMember::find($current->parent_id) : null;
            $guard++;
        }

        return false;
    }
}
