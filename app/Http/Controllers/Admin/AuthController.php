<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin');
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = $credentials['email'];
        $throttleKey = 'admin_login:' . $email . ':' . $request->ip();

        // Check rate limit: max 5 attempts per minute
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json(['message' => "登录尝试次数过多，请{$seconds}秒后再试"], 429);
        }

        $admin = AdminUser::where('email', $email)->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json(['message' => '账号或密码错误'], 422);
        }

        // Clear throttle on success
        RateLimiter::clear($throttleKey);

        // Login admin using the admin guard
        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json(['message' => '登录成功', 'user' => $admin]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => '已退出登录']);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            return response()->json($admin);
        }
        return response()->json(null);
    }
}
