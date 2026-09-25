<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Site;
use App\Services\UrlFetcherService;
use App\Traits\ClearsDashboardCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteManagementController extends Controller
{
    use ClearsDashboardCache;

    public function data(Request $request): JsonResource
    {
        $request->validate(['limit' => 'integer|min:1|max:100']);

        $query = Site::with('category');

        if ($request->filled('keyword')) {
            $keyword = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('keyword'));
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('url', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_public') && $request->input('is_public') !== '') {
            $query->where('is_public', $request->boolean('is_public'));
        }

        $sites = $query->orderBy('sort_order')->orderBy('id', 'desc')
            ->paginate((int) $request->input('limit', 15));

        return JsonResource::make($sites)->additional([
            'code' => 0,
            'msg' => '',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Lengths must match the varchar(255) columns — anything longer
        // would be a strict-mode insert error, not a validation error
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:255|unique:sites,url',
            'description' => 'nullable|string|max:255',
            'favicon_url' => 'nullable|url|max:255',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_public'] = $data['is_public'] ?? true;
        $data['is_active'] = $data['is_active'] ?? true;
        $data = array_filter($data, fn ($v) => ! is_null($v));

        $site = Site::create($data);
        $this->clearDashboardCache();

        return response()->json(['code' => 0, 'msg' => '添加成功', 'data' => $site]);
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        // Lengths must match the varchar(255) columns — anything longer
        // would be a strict-mode insert error, not a validation error
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:255|unique:sites,url,'.$site->id,
            'description' => 'nullable|string|max:255',
            'favicon_url' => 'nullable|url|max:255',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // description/favicon_url are nullable (null clears them); explicit
        // nulls on NOT NULL columns would violate strict mode
        $data = collect($data)->reject(
            fn ($v, $key) => is_null($v) && ! in_array($key, ['description', 'favicon_url']),
        )->all();

        $site->update($data);
        $this->clearDashboardCache();

        return response()->json(['code' => 0, 'msg' => '更新成功']);
    }

    public function destroy(Site $site): JsonResponse
    {
        $site->delete();
        $this->clearDashboardCache();

        return response()->json(['code' => 0, 'msg' => '删除成功']);
    }

    public function fetchUrl(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url|max:2048']);
        $result = app(UrlFetcherService::class)->fetch($request->url);

        return response()->json($result);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::active()->ordered()->get(['id', 'name']);

        return response()->json(['code' => 0, 'data' => $categories]);
    }
}
