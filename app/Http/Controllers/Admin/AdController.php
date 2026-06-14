<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    public function index(): JsonResponse
    {
        $ads = Ad::orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['code' => 0, 'data' => $ads]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'image_url' => 'required|string|max:500',
            'link_url' => 'required|url|max:500',
            'position' => 'required|string|in:content_between,sidebar_bottom,footer_above',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'target' => 'nullable|string|in:_blank,_self',
        ]);

        $ad = Ad::create($data);

        return response()->json(['code' => 0, 'msg' => '添加成功', 'data' => $ad], 201);
    }

    public function update(Request $request, Ad $ad): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:100',
            'image_url' => 'sometimes|string|max:500',
            'link_url' => 'sometimes|url|max:500',
            'position' => 'sometimes|string|in:content_between,sidebar_bottom,footer_above',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'target' => 'nullable|string|in:_blank,_self',
        ]);

        $ad->update($data);

        return response()->json(['code' => 0, 'msg' => '更新成功', 'data' => $ad]);
    }

    public function destroy(Ad $ad): JsonResponse
    {
        $ad->delete();

        return response()->json(['code' => 0, 'msg' => '删除成功']);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $file = $request->file('image');
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return response()->json(['code' => 1, 'msg' => '文件不是有效的图片'], 422);
        }

        $path = $file->store('ads', 'public');

        return response()->json([
            'code' => 0,
            'msg' => '上传成功',
            'data' => ['url' => Storage::url($path)],
        ]);
    }
}
