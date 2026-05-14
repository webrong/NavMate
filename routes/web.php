<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\Api\UserLayoutController;
use App\Http\Controllers\Api\UserLinkController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookmarkImportController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FriendLinkController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\SiteManagementController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Routes (sitemap, robots.txt)
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');

/*
|--------------------------------------------------------------------------
| Install Wizard (available only when not installed)
|--------------------------------------------------------------------------
*/

Route::prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('install');
    Route::post('/check-environment', [InstallController::class, 'checkEnvironment'])->name('install.check-environment');
    Route::post('/test-database', [InstallController::class, 'testDatabase'])->name('install.test-database');
    Route::post('/test-redis', [InstallController::class, 'testRedis'])->name('install.test-redis');
    Route::post('/execute', [InstallController::class, 'execute'])->middleware('throttle:3,1')->name('install.execute');
});

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
Route::post('/api/quick-add', [SiteController::class, 'quickAdd'])->middleware(['auth', 'throttle:10,1'])->name('api.quick-add');
Route::post('/api/click', [SiteController::class, 'click'])->middleware('throttle:60,1')->name('api.click');
Route::get('/api/search', [SiteController::class, 'search'])->middleware('throttle:60,1')->name('api.search');
Route::get('/api/categories', [ApiCategoryController::class, 'index'])->middleware('throttle:60,1')->name('api.categories');
Route::get('/api/settings', [SettingsController::class, 'publicSettings'])->middleware('throttle:60,1')->name('api.settings');
Route::get('/api/friend-links', function () {
    return response()->json(\App\Models\FriendLink::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get());
});

Route::get('/api/ads', function () {
    return response()->json(\App\Models\Ad::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'title', 'image_url', 'link_url', 'position', 'target']));
});

// Auth API
Route::get('/api/user', [ApiAuthController::class, 'me'])->name('api.user');
Route::post('/api/register', [ApiAuthController::class, 'register'])->middleware('throttle:3,1')->name('api.register');
Route::post('/api/login', [ApiAuthController::class, 'login'])->middleware('throttle:5,1')->name('api.login');
Route::post('/api/logout', [ApiAuthController::class, 'logout'])->name('api.logout');
Route::post('/api/forgot-password', [ApiAuthController::class, 'forgotPassword'])->middleware('throttle:3,1')->name('api.forgot-password');
Route::post('/api/reset-password', [ApiAuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('api.reset-password');
Route::post('/api/resend-verification', [ApiAuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('api.resend-verification');

// Email verification (backend route for signed URL in email)
Route::get('/email/verify/{id}/{hash}', [ApiAuthController::class, 'verifyEmail'])->middleware(['signed'])->name('verification.verify');

// User features API (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/api/user/favorites', [FavoriteController::class, 'index'])->name('api.favorites.index');
    Route::post('/api/user/favorites', [FavoriteController::class, 'store'])->name('api.favorites.store');
    Route::delete('/api/user/favorites/{site_id}', [FavoriteController::class, 'destroy'])->name('api.favorites.destroy');
    Route::get('/api/user/layout', [UserLayoutController::class, 'show'])->name('api.layout.show');
    Route::put('/api/user/layout', [UserLayoutController::class, 'update'])->name('api.layout.update');
    Route::get('/api/user/links', [UserLinkController::class, 'index'])->name('api.links.index');
    Route::post('/api/user/links', [UserLinkController::class, 'store'])->name('api.links.store');
    Route::put('/api/user/links/reorder', [UserLinkController::class, 'reorder'])->name('api.links.reorder');
    Route::put('/api/user/links/{link}', [UserLinkController::class, 'update'])->name('api.links.update');
    Route::delete('/api/user/links/{link}', [UserLinkController::class, 'destroy'])->name('api.links.destroy');
    Route::put('/api/user/profile', [ApiAuthController::class, 'updateProfile'])->name('api.user.profile');
    Route::post('/api/user/avatar', [ApiAuthController::class, 'uploadAvatar'])->name('api.user.avatar');
});

/*
|--------------------------------------------------------------------------
| Admin Auth
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin/logout', [AuthController::class, 'logout'])->middleware('auth:admin')->name('admin.logout');
Route::get('/admin/api/me', [AuthController::class, 'me'])->middleware('auth:admin')->name('admin.me');

/*
|--------------------------------------------------------------------------
| Admin API (auth + admin required, returns JSON)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/api')->middleware(['auth:admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Categories
    Route::get('/categories', [CategoryController::class, 'data'])->name('admin.categories.data');
    Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::get('/categories/tree', [CategoryController::class, 'tree'])->name('admin.categories.tree');

    // Sites
    Route::get('/sites', [SiteManagementController::class, 'data'])->name('admin.sites.data');
    Route::post('/sites', [SiteManagementController::class, 'store'])->name('admin.sites.store');
    Route::post('/sites/fetch-url', [SiteManagementController::class, 'fetchUrl'])->name('admin.sites.fetch-url');
    Route::get('/sites/categories', [SiteManagementController::class, 'categories'])->name('admin.sites.categories');
    Route::put('/sites/{site}', [SiteManagementController::class, 'update'])->name('admin.sites.update');
    Route::delete('/sites/{site}', [SiteManagementController::class, 'destroy'])->name('admin.sites.destroy');

    // Users
    Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');

    // Analytics
    Route::get('/analytics/trends', [AnalyticsController::class, 'trends'])->name('admin.analytics.trends');
    Route::get('/analytics/top-sites', [AnalyticsController::class, 'topSites'])->name('admin.analytics.top-sites');
    Route::get('/analytics/top-categories', [AnalyticsController::class, 'topCategories'])->name('admin.analytics.top-categories');
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary'])->name('admin.analytics.summary');
    Route::get('/analytics/hourly', [AnalyticsController::class, 'hourlyDistribution'])->name('admin.analytics.hourly');
    Route::get('/analytics/recent-clicks', [AnalyticsController::class, 'recentClicks'])->name('admin.analytics.recent-clicks');

    // Bookmark Import
    Route::post('/bookmarks/preview', [BookmarkImportController::class, 'preview'])->name('admin.bookmarks.preview');
    Route::post('/bookmarks/import', [BookmarkImportController::class, 'import'])->name('admin.bookmarks.import');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('/settings/upload-image', [SettingsController::class, 'uploadImage'])->name('admin.settings.upload-image');
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->middleware('throttle:3,1')->name('admin.settings.test-email');

    // Friend Links
    Route::get('/friend-links', [FriendLinkController::class, 'index'])->name('admin.friend-links.index');
    Route::post('/friend-links', [FriendLinkController::class, 'store'])->name('admin.friend-links.store');
    Route::put('/friend-links/{friendLink}', [FriendLinkController::class, 'update'])->name('admin.friend-links.update');
    Route::delete('/friend-links/{friendLink}', [FriendLinkController::class, 'destroy'])->name('admin.friend-links.destroy');

    // Ads
    Route::get('/ads', [AdController::class, 'index'])->name('admin.ads.index');
    Route::post('/ads', [AdController::class, 'store'])->name('admin.ads.store');
    Route::post('/ads/upload-image', [AdController::class, 'uploadImage'])->name('admin.ads.upload-image');
    Route::put('/ads/{ad}', [AdController::class, 'update'])->name('admin.ads.update');
    Route::delete('/ads/{ad}', [AdController::class, 'destroy'])->name('admin.ads.destroy');

    // System
    Route::get('/system/info', [SystemController::class, 'info'])->name('admin.system.info');
    Route::get('/system/check-update', [SystemController::class, 'checkUpdate'])->name('admin.system.check-update');
    Route::post('/system/update', [SystemController::class, 'update'])->middleware('throttle:1,60')->name('admin.system.update');
    Route::get('/system/update-logs', [SystemController::class, 'updateLogs'])->name('admin.system.update-logs');
    Route::post('/system/clear-cache', [SystemController::class, 'clearCache'])->name('admin.system.clear-cache');
});

/*
|--------------------------------------------------------------------------
| Admin SPA Catch-all (must be after all admin routes)
|--------------------------------------------------------------------------
*/

Route::get('/admin/{any}', function () {
    return view('admin');
})->where('any', '.*')->name('admin.spa');

/*
|--------------------------------------------------------------------------
| Frontend SPA Catch-all (must be last)
|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('spa');
})->where('any', '(?!admin)[a-zA-Z0-9\-/]+');
