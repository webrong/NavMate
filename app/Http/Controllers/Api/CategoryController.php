<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $sitesByCategory = Site::active()
            ->public()
            ->ordered()
            ->get()
            ->groupBy('category_id');

        $allCategories = Category::active()
            ->ordered()
            ->get()
            ->keyBy('id');

        $allCategories->each(function ($category) use ($sitesByCategory) {
            $category->sites = $sitesByCategory->get($category->id, collect());
        });

        $childCategories = $allCategories->filter(fn($c) => $c->parent_id !== null);
        $parentCategories = $allCategories->filter(fn($c) => $c->parent_id === null);

        $categories = $parentCategories->map(function ($parent) use ($childCategories) {
            $parent->children = $childCategories->where('parent_id', $parent->id)->values();
            return $parent;
        })->filter(fn($c) => $c->sites->isNotEmpty() || $c->children->isNotEmpty())->values();

        return response()->json($categories);
    }
}
