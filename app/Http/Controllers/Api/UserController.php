<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 50), 1), 200);

        $q = User::query()->select(['id', 'name', 'email', 'phone', 'role', 'unit', 'is_active', 'is_protected', 'created_at'])
            ->when($request->role, fn ($qq, $v) => $qq->where('role', $v))
            ->when($request->search, fn ($qq, $v) => $qq->where(fn ($q2) => $q2->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->when($request->has('is_active'), fn ($qq) => $qq->where('is_active', (bool) $request->is_active));

        return $q->orderBy('name')->paginate($perPage);
    }

    public function stats()
    {
        $row = User::query()->selectRaw(
            'COUNT(*) as total, COALESCE(SUM(CASE WHEN role = ? THEN 1 ELSE 0 END), 0) as total_guru',
            [User::ROLE_GURU],
        )->first();

        return response()->json([
            'total' => (int) $row->total,
            'guru' => (int) $row->total_guru,
        ]);
    }
}
