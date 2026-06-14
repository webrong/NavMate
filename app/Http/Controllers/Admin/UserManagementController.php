<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $query = User::query();

        if ($request->filled('keyword')) {
            $keyword = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('keyword'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $users = $query->orderBy('id', 'desc')
            ->paginate($request->input('limit', 15));

        return JsonResource::make($users)->additional([
            'code' => 0,
            'msg' => '',
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'is_admin' => 'boolean',
            'name' => 'sometimes|string|max:255',
        ]);

        // Prevent removing admin role from the last admin
        if (isset($data['is_admin']) && ! $data['is_admin'] && $user->is_admin) {
            $adminCount = User::where('is_admin', true)->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => '不能取消最后一个管理员权限'], 422);
            }
        }

        if (array_key_exists('is_admin', $data)) {
            $user->is_admin = $data['is_admin'];
        }
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        $user->save();

        return response()->json(['code' => 0, 'msg' => '更新成功', 'data' => $user->fresh()]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        // Prevent deleting the last admin
        if ($user->is_admin) {
            $adminCount = User::where('is_admin', true)->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => '不能删除最后一个管理员用户'], 422);
            }
        }

        $user->delete();

        return response()->json(['code' => 0, 'msg' => '删除成功']);
    }
}
