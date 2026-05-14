<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLink;
use App\Services\UrlFetcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLinkController extends Controller
{
    /**
     * Verify the link belongs to the authenticated user.
     */
    private function authorizeOwnership(UserLink $link, Request $request): void
    {
        if ($link->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $links = $request->user()->links()->get();

        return response()->json($links);
    }

    public function store(Request $request, UrlFetcherService $fetcher): JsonResponse
    {
        $data = $request->validate([
            'url' => 'required|url|max:2048',
            'title' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $maxSort = $user->links()->max('sort_order') ?? 0;

        // Auto-fetch title and favicon
        $meta = $fetcher->fetch($data['url']);
        $title = $data['title'] ?: ($meta['title'] ?? parse_url($data['url'], PHP_URL_HOST));
        $faviconUrl = $meta['favicon_url'];

        $link = $user->links()->create([
            'title' => $title ?? parse_url($data['url'], PHP_URL_HOST),
            'url' => $data['url'],
            'favicon_url' => $faviconUrl,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json($link, 201);
    }

    public function update(Request $request, UserLink $link): JsonResponse
    {
        $this->authorizeOwnership($link, $request);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|url|max:2048',
            'sort_order' => 'sometimes|integer',
        ]);

        $link->update($data);

        return response()->json($link);
    }

    public function destroy(Request $request, UserLink $link): JsonResponse
    {
        $this->authorizeOwnership($link, $request);

        $link->delete();

        return response()->json(['message' => '已删除']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.sort_order' => 'required|integer',
        ]);

        $user = $request->user();

        foreach ($data['items'] as $item) {
            $link = UserLink::where('id', $item['id'])
                ->where('user_id', $user->id)
                ->first();
            if ($link) {
                $link->update(['sort_order' => $item['sort_order']]);
            }
        }

        return response()->json(['message' => '排序已更新']);
    }
}
