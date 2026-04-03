<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Site;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // 1. 获取所有活跃站点，按分类分组
        $sitesByCategory = Site::active()
            ->public()
            ->ordered()
            ->get()
            ->groupBy('category_id');

        // 2. 获取所有活跃分类
        $allCategories = Category::active()
            ->ordered()
            ->get()
            ->keyBy('id');

        // 3. 为每个分类附加站点
        $allCategories->each(function ($category) use ($sitesByCategory) {
            $category->sites = $sitesByCategory->get($category->id, collect());
        });

        // 4. 分离父分类和子分类
        $childCategories = $allCategories->filter(fn($c) => $c->parent_id !== null);
        $parentCategories = $allCategories->filter(fn($c) => $c->parent_id === null);

        // 5. 构建展示用的分类树
        // 每个父分类附带 children（子分类集合），每个子分类/父分类都有 sites
        // 同时兼容：
        //   - 有子分类的父分类：父分类显示为标题 + tab，子分类作为 tab 项
        //   - 无子分类的父分类（扁平模式）：父分类自身作为唯一 tab，标题即分类名
        $categories = $parentCategories->map(function ($parent) use ($childCategories) {
            $parent->children = $childCategories->where('parent_id', $parent->id)->values();
            return $parent;
        })->filter(fn($c) => $c->sites->isNotEmpty() || $c->children->isNotEmpty())->values();

        return view('home', compact('categories'));
    }
}
