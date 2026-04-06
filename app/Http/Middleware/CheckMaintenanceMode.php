<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('maintenance_mode') === '1') {
            // Allow admin routes only for authenticated admins
            if ($request->is('admin*') && \Auth::guard('admin')->check()) {
                return $next($request);
            }

            // Allow admin login page (needed to authenticate)
            if ($request->is('admin/login')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => '站点维护中，请稍后再试', 'maintenance' => true], 503);
            }

            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
