<?php

namespace App\Http\Middleware;

use App\Services\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in console context (artisan commands)
        if (app()->runningInConsole()) {
            return $next($request);
        }

        // Skip in local/development environment (testing uses sqlite :memory:,
        // so the install state is irrelevant and would only break tests)
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $isInstalled = InstallerService::isInstalled();

        if ($isInstalled) {
            // Lock install routes after installation
            if ($request->is('install') || $request->is('install/*')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => '应用已安装，如需重新安装请删除 storage/app/installed 文件',
                    ], 403);
                }

                return response()->view('errors.installed', [], 403);
            }

            return $next($request);
        }

        // Not installed: force file-based sessions (database may not exist)
        config(['session.driver' => 'file']);

        // Allow install routes and health check
        if ($request->is('install') || $request->is('install/*') || $request->is('up')) {
            return $next($request);
        }

        // Redirect everything else to installer
        return redirect('/install');
    }
}
