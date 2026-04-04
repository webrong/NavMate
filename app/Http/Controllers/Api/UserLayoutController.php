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
            'layout_data' => 'required|array',
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
