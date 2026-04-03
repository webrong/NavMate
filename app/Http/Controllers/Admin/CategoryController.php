<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index');
    }

    public function data(Request $request): JsonResource
    {
        $query = Category::query();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('name', 'like', "%{$keyword}%");
        }

        $categories = $query->orderBy('sort_order')->orderBy('id', 'desc')
            ->paginate($request->input('limit', 15));

        return JsonResource::make($categories)->additional([
            'code' => 0,
            'msg' => '',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:categories,slug',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:10',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        $category = Category::create($data);

        return response()->json(['code' => 0, 'msg' => '添加成功', 'data' => $category]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:10',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        $category->update($data);

        return response()->json(['code' => 0, 'msg' => '更新成功']);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['code' => 0, 'msg' => '删除成功']);
    }
}
