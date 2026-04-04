<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\UserLayoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookmarkImportController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SiteManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Frontend
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('spa');
})->name('home');

/*
|--------------------------------------------------------------------------
| Public API (no auth required, returns JSON)
|--------------------------------------------------------------------------
*/

Route::post('/api/fetch-url', [SiteController::class, 'fetchUrl'])->middleware('throttle:30,1')->name('api.fetch-url');
Route::post('/api/quick-add', [SiteController::class, 'quickAdd'])->middleware('throttle:10,1')->name('api.quick-add');
Route::post('/api/click', [SiteController::class, 'click'])->middleware('throttle:60,1')->name('api.click');
Route::get('/api/search', [SiteController::class, 'search'])->middleware('throttle:60,1')->name('api.search');
Route::get('/api/categories', [ApiCategoryController::class, 'index'])->middleware('throttle:60,1')->name('api.categories');

// Auth API
Route::get('/api/user', [ApiAuthController::class, 'me'])->name('api.user');
Route::post('/api/register', [ApiAuthController::class, 'register'])->middleware('throttle:3,1')->name('api.register');
Route::post('/api/login', [ApiAuthController::class, 'login'])->middleware('throttle:5,1')->name('api.login');
Route::post('/api/logout', [ApiAuthController::class, 'logout'])->name('api.logout');

// User features API (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/api/user/favorites', [FavoriteController::class, 'index'])->name('api.favorites.index');
    Route::post('/api/user/favorites', [FavoriteController::class, 'store'])->name('api.favorites.store');
    Route::delete('/api/user/favorites/{site_id}', [FavoriteController::class, 'destroy'])->name('api.favorites.destroy');
    Route::get('/api/user/layout', [UserLayoutController::class, 'show'])->name('api.layout.show');
    Route::put('/api/user/layout', [UserLayoutController::class, 'update'])->name('api.layout.update');
});

/*
|--------------------------------------------------------------------------
| Admin Auth
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Backend (auth required)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/categories/data', [CategoryController::class, 'data'])->name('admin.categories.data');
    Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Sites
    Route::get('/sites', [SiteManagementController::class, 'index'])->name('admin.sites.index');
    Route::get('/sites/data', [SiteManagementController::class, 'data'])->name('admin.sites.data');
    Route::post('/sites', [SiteManagementController::class, 'store'])->name('admin.sites.store');
    Route::post('/sites/fetch-url', [SiteManagementController::class, 'fetchUrl'])->name('admin.sites.fetch-url');
    Route::put('/sites/{site}', [SiteManagementController::class, 'update'])->name('admin.sites.update');
    Route::delete('/sites/{site}', [SiteManagementController::class, 'destroy'])->name('admin.sites.destroy');

    // Bookmark Import
    Route::get('/bookmarks', [BookmarkImportController::class, 'index'])->name('admin.bookmarks.index');
    Route::post('/bookmarks/preview', [BookmarkImportController::class, 'preview'])->name('admin.bookmarks.preview');
    Route::post('/bookmarks/import', [BookmarkImportController::class, 'import'])->name('admin.bookmarks.import');
});

/*
|--------------------------------------------------------------------------
| SPA Catch-all (must be last)
|--------------------------------------------------------------------------
*/
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '[a-zA-Z0-9\-/]+');
