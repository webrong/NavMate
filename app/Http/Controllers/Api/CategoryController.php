<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryTreeService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(CategoryTreeService $treeService): JsonResponse
    {
        $categories = $treeService->getPublicTree();

        return response()->json($categories);
    }
}
