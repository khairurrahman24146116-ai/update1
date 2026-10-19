<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrganizationMember;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StructureMemberController extends Controller
{
    use AuthorizesRequests;

    /**
     * Public read-only hierarchy for a single unit.
     */
    public function publicShow(string $unit): JsonResponse
    {
        $unit = strtolower($unit);

        if (! in_array($unit, OrganizationMember::UNITS, true)) {
            return response()->json(['message' => 'Unit struktur tidak dikenal.'], 422);
        }

        $members = OrganizationMember::query()
            ->unit($unit)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'unit' => $unit,
            'label' => OrganizationMember::UNIT_LABELS[$unit],
            'tree' => $this->buildTree($members),
        ]);
    }

    /**
     * Flat list for the admin panel (optionally scoped to one unit).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrganizationMember::class);

        $unit = $request->query('unit');
        if ($unit !== null && ! in_array($unit, OrganizationMember::UNITS, true)) {
            return response()->json(['message' => 'Unit struktur tidak dikenal.'], 422);
        }

        $query = OrganizationMember::query();
        if ($unit !== null) {
            $query->unit($unit);
        }

        return response()->json([
            'members' => $query->orderBy('unit')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', OrganizationMember::class);

        $validated = $request->validate([
            'unit' => ['required', 'string', Rule::in(OrganizationMember::UNITS)],
            'parent_id' => ['nullable', 'integer', 'exists:organization_members,id'],
            'name' => ['nullable', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_confirmed' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $parent = $this->resolveParent($validated['unit'], $validated['parent_id'] ?? null);
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        if ($parent !== null && $this->depthOf($parent) + 1 > OrganizationMember::MAX_DEPTH) {
            return $this->depthError();
        }

        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = ((int) OrganizationMember::query()
                ->where('unit', $validated['unit'])
                ->where('parent_id', $validated['parent_id'] ?? null)
                ->max('sort_order')) + 1;
        }

        $member = OrganizationMember::create($validated);

        ActivityLogger::log('structure.create', $request->user(), OrganizationMember::class, $member->id, $validated);

        return response()->json([
            'member' => $member,
            'message' => 'Anggota struktur berhasil ditambahkan.',
        ], 201);
    }

    public function update(Request $request, OrganizationMember $organizationMember): JsonResponse
    {
        $this->authorize('update', $organizationMember);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:organization_members,id'],
            'name' => ['nullable', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_confirmed' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $parent = $this->resolveParentFor($organizationMember, array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $organizationMember->parent_id);
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        if ($parent !== null && $this->depthOf($parent) + 1 + $this->heightOf($organizationMember) > OrganizationMember::MAX_DEPTH) {
            return $this->depthError();
        }

        $oldPhoto = $organizationMember->photo_path;
        $organizationMember->update($validated);

        if (array_key_exists('photo_path', $validated)
            && $oldPhoto
            && $oldPhoto !== $organizationMember->photo_path) {
            $this->deleteManagedPhoto($oldPhoto);
        }

        ActivityLogger::log('structure.update', $request->user(), OrganizationMember::class, $organizationMember->id, $validated);

        return response()->json([
            'member' => $organizationMember->fresh(),
            'message' => 'Anggota struktur berhasil diperbarui.',
        ]);
    }

    public function destroy(Request $request, OrganizationMember $organizationMember): JsonResponse
    {
        $this->authorize('delete', $organizationMember);

        $this->deleteManagedPhoto($organizationMember->photo_path);

        OrganizationMember::query()
            ->where('parent_id', $organizationMember->id)
            ->update(['parent_id' => $organizationMember->parent_id]);

        $id = $organizationMember->id;
        $organizationMember->delete();

        ActivityLogger::log('structure.delete', $request->user(), OrganizationMember::class, $id, ['id' => $id]);

        return response()->json(['message' => 'Anggota struktur berhasil dihapus.']);
    }

    /**
     * Move a member up or down among its siblings.
     */
    public function move(Request $request, OrganizationMember $organizationMember): JsonResponse
    {
        $this->authorize('update', $organizationMember);

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $siblings = OrganizationMember::query()
            ->where('unit', $organizationMember->unit)
            ->where('parent_id', $organizationMember->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (OrganizationMember $m) => $m->id === $organizationMember->id);
        if ($index === false) {
            return response()->json(['message' => 'Anggota tidak ditemukan pada tingkatnya.'], 422);
        }

        $target = $validated['direction'] === 'up' ? $index - 1 : $index + 1;
        if ($target < 0 || $target >= $siblings->count()) {
            return response()->json(['message' => 'Posisi sudah di batas urutan.'], 422);
        }

        $reordered = $siblings->all();
        [$reordered[$index], $reordered[$target]] = [$reordered[$target], $reordered[$index]];

        foreach ($reordered as $position => $member) {
            $member->forceFill(['sort_order' => $position + 1])->save();
        }

        return response()->json(['message' => 'Urutan berhasil diperbarui.']);
    }

    /**
     * Upload a member photo (admin). Content type is inspected, SVG is rejected.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $this->authorize('create', OrganizationMember::class);

        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
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

        ActivityLogger::log('structure.upload_photo', $request->user(), OrganizationMember::class, null, ['path' => $filename]);

        return response()->json([
            'path' => $filename,
            'message' => 'Foto berhasil diunggah.',
        ]);
    }

    private function resolveParent(string $unit, ?int $parentId): OrganizationMember|JsonResponse|null
    {
        if ($parentId === null) {
            return null;
        }

        $parent = OrganizationMember::find($parentId);

        if ($parent === null) {
            return response()->json(['message' => 'Atasan tidak ditemukan.'], 422);
        }

        if ($parent->unit !== $unit) {
            return response()->json(['message' => 'Atasan harus berasal dari unit yang sama.'], 422);
        }

        return $parent;
    }

    private function resolveParentFor(OrganizationMember $member, ?int $parentId): OrganizationMember|JsonResponse|null
    {
        if ($parentId === null) {
            return null;
        }

        if ($parentId === $member->id) {
            return response()->json(['message' => 'Atasan tidak valid: anggota tidak dapat menjadi atasan dirinya sendiri.'], 422);
        }

        if ($this->wouldCreateCycle($member->id, $parentId)) {
            return response()->json(['message' => 'Atasan tidak valid: hubungan tersebut akan membentuk siklus hierarki.'], 422);
        }

        $parent = OrganizationMember::find($parentId);

        if ($parent === null) {
            return response()->json(['message' => 'Atasan tidak ditemukan.'], 422);
        }

        if ($parent->unit !== $member->unit) {
            return response()->json(['message' => 'Atasan harus berasal dari unit yang sama.'], 422);
        }

        return $parent;
    }

    private function depthError(): JsonResponse
    {
        return response()->json([
            'message' => 'Tingkat struktur paling banyak '.OrganizationMember::MAX_DEPTH.' tingkat di bawah puncak.',
        ], 422);
    }

    /**
     * Number of edges between the node and the root (root = 0).
     */
    private function depthOf(OrganizationMember $node): int
    {
        $depth = 0;
        $current = $node;
        $guard = 0;

        while ($current->parent_id !== null && $guard < 100) {
            $current = OrganizationMember::find($current->parent_id);
            if ($current === null) {
                break;
            }
            $depth++;
            $guard++;
        }

        return $depth;
    }

    /**
     * Number of levels below the node (leaf = 0).
     */
    private function heightOf(OrganizationMember $node): int
    {
        $children = OrganizationMember::query()->where('parent_id', $node->id)->get();
        if ($children->isEmpty()) {
            return 0;
        }

        return 1 + (int) $children->max(fn (OrganizationMember $child) => $this->heightOf($child));
    }

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

    private function deleteManagedPhoto(?string $path): void
    {
        if (! $path || Str::startsWith($path, '/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  Collection<int, OrganizationMember>  $members
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(Collection $members, ?int $parentId = null): array
    {
        return $members
            ->where('parent_id', $parentId)
            ->map(function (OrganizationMember $member) use ($members) {
                return [
                    'id' => $member->id,
                    'parent_id' => $member->parent_id,
                    'unit' => $member->unit,
                    'name' => $member->name,
                    'position' => $member->position,
                    'photo' => $member->photo_path,
                    'sort_order' => $member->sort_order,
                    'is_confirmed' => $member->is_confirmed,
                    'children' => $this->buildTree($members, $member->id),
                ];
            })
            ->values()
            ->all();
    }
}
