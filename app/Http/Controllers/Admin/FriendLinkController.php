<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FriendLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $links = FriendLink::orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['code' => 0, 'data' => $links]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'logo' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // NOT NULL columns (sort_order/is_active) get their DB defaults on
        // create; logo is nullable so a null still clears it
        $data = array_filter($data, fn ($v) => ! is_null($v));

        $link = FriendLink::create($data);

        return response()->json(['code' => 0, 'msg' => '添加成功', 'data' => $link], 201);
    }

    public function update(Request $request, FriendLink $friendLink): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'url' => 'sometimes|url|max:500',
            'logo' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Explicit nulls on NOT NULL columns would violate strict mode —
        // only logo is nullable, so null there still clears it
        $data = collect($data)->reject(
            fn ($v, $key) => is_null($v) && $key !== 'logo',
        )->all();

        $friendLink->update($data);

        return response()->json(['code' => 0, 'msg' => '更新成功', 'data' => $friendLink]);
    }

    public function destroy(FriendLink $friendLink): JsonResponse
    {
        $friendLink->delete();

        return response()->json(['code' => 0, 'msg' => '删除成功']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:friend_links,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->ids as $i => $id) {
                FriendLink::where('id', $id)->update(['sort_order' => $i]);
            }
        });

        return response()->json(['code' => 0, 'msg' => '排序已更新']);
    }
}
