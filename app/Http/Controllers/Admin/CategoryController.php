<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ClearsDashboardCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use ClearsDashboardCache;

    public function data(Request $request): JsonResponse
    {
        $query = Category::query()->withCount('sites');

        if ($request->filled('keyword')) {
            $keyword = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('keyword'));
            $query->where('name', 'like', "%{$keyword}%");
        }

        $categories = $query->orderBy('sort_order')->orderBy('id', 'desc')->get();

        // Build tree
        $tree = $categories->whereNull('parent_id')->values();
        $tree->each(function ($item) use ($categories) {
            $this->buildChildren($item, $categories);
        });

        return response()->json(['code' => 0, 'data' => $tree]);
    }

    private function buildChildren($parent, $all): void
    {
        $children = $all->where('parent_id', $parent->id)->values();
        $children->each(fn($c) => $this->buildChildren($c, $all));
        $parent->setAttribute('children', $children->values()->all());
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

        if (!empty($data['parent_id']) && !$this->isValidParent(null, $data['parent_id'])) {
            return response()->json(['message' => '无效的父级分类'], 422);
        }

        // Remove null values so database defaults apply (icon, sort_order, etc.)
        $data = array_filter($data, fn($v) => $v !== null);

        try {
            $category = Category::create($data);
            $this->clearDashboardCache();
        } catch (\Throwable $e) {
            Log::error('Category create failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => '创建失败: ' . $e->getMessage(),
            ], 500);
        }

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

        if (array_key_exists('parent_id', $data)) {
            // Cannot set self as parent
            if ($data['parent_id'] == $category->id) {
                return response()->json(['message' => '不能将自己设为父级分类'], 422);
            }
            // Cannot set a descendant as parent (would create circular reference)
            if (!empty($data['parent_id']) && !$this->isValidParent($category->id, $data['parent_id'])) {
                return response()->json(['message' => '不能将子级分类设为父级，会产生循环引用'], 422);
            }
        }

        $category->update(array_filter($data, fn($v) => $v !== null));
        $this->clearDashboardCache();

        return response()->json(['code' => 0, 'msg' => '更新成功']);
    }

    /**
     * Check if parentId is valid (not a descendant of excludeId)
     */
    private function isValidParent(?int $excludeId, int $parentId): bool
    {
        if ($excludeId !== null && $parentId == $excludeId) {
            return false;
        }

        // Walk up the parent chain to detect circular reference
        $visited = [$parentId];
        $current = Category::find($parentId);
        while ($current && $current->parent_id) {
            if ($current->parent_id == $excludeId) {
                return false;
            }
            if (in_array($current->parent_id, $visited)) {
                return false; // existing circular reference
            }
            $visited[] = $current->parent_id;
            $current = Category::find($current->parent_id);
        }

        return true;
    }

    public function destroy(Category $category): JsonResponse
    {
        $siteCount = $category->sites()->count();
        $childCount = Category::where('parent_id', $category->id)->count();

        if ($siteCount > 0 || $childCount > 0) {
            return response()->json([
                'message' => "该分类下有 {$siteCount} 个站点和 {$childCount} 个子分类，请先移动或删除",
            ], 422);
        }

        $category->delete();
        $this->clearDashboardCache();
        return response()->json(['code' => 0, 'msg' => '删除成功']);
    }

    public function tree(): JsonResponse
    {
        $categories = Category::active()->ordered()->get();
        return response()->json(['code' => 0, 'data' => $categories]);
    }
}
