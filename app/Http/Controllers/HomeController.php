<?php

namespace App\Http\Controllers;

use App\Services\CategoryTreeService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(CategoryTreeService $treeService): View
    {
        $categories = $treeService->getPublicTree();

        return view('home', compact('categories'));
    }
}
