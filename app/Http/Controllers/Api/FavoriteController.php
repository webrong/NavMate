<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with('site')
            ->get();

        return response()->json($favorites);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_id' => 'required|exists:sites,id',
        ]);

        $favorite = $request->user()
            ->favorites()
            ->firstOrCreate(['site_id' => $data['site_id']]);

        return response()->json($favorite, 201);
    }

    public function destroy(Request $request, int $siteId): JsonResponse
    {
        $request->user()
            ->favorites()
            ->where('site_id', $siteId)
            ->delete();

        return response()->json(['message' => '已取消收藏']);
    }
}
