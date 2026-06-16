<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'layout_data.*.category_id' => 'required|exists:categories,id',
            'layout_data.*.visible' => 'required|boolean',
            'layout_data.*.sort_order' => 'required|integer',
        ]);

        $layout = UserLayout::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['layout_data' => $data['layout_data']]
        );

        return response()->json($layout);
    }
}
