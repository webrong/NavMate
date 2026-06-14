<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Site;
use Illuminate\Support\Collection;

class CategoryTreeService
{
    /**
     * Build the category tree with sites for the public frontend.
     */
    public function getPublicTree(): Collection
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

        $childCategories = $allCategories->filter(fn ($c) => $c->parent_id !== null);
        $parentCategories = $allCategories->filter(fn ($c) => $c->parent_id === null);

        return $parentCategories->map(function ($parent) use ($childCategories) {
            $parent->children = $childCategories->where('parent_id', $parent->id)->values();

            return $parent;
        })->filter(fn ($c) => $c->sites->isNotEmpty() || $c->children->isNotEmpty())->values();
    }
}
