<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\UserLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLayoutController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $layout = $request->user()->layout;

        return response()->json($layout?->layout_data ?? []);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Cap the number of layout entries to guard the JSON column against
            // abuse. A real layout has one entry per category; 200 is far above
            // any plausible category count.
            'layout_data' => 'required|array|max:200',
            'layout_data.*.category_id' => 'required|integer',
            'layout_data.*.visible' => 'required|boolean',
            'layout_data.*.sort_order' => 'required|integer',
        ]);

        // One whereIn check instead of an exists: query per entry — a 200-entry
        // layout would otherwise fire up to 200 queries
        $categoryIds = array_unique(array_column($data['layout_data'], 'category_id'));
        if (Category::whereIn('id', $categoryIds)->count() !== count($categoryIds)) {
            return response()->json(['message' => '布局包含无效的分类'], 422);
        }

        $layout = UserLayout::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['layout_data' => $data['layout_data']]
        );

        return response()->json($layout);
    }
}
