<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            return response()->json($user);
        }
        return response()->json(null);
    }

    public function register(Request $request): JsonResponse
    {
        // Check if registration is enabled
        if (\App\Models\Setting::get('enable_register') === '0') {
            return response()->json(['message' => '注册功能已关闭'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        Log::info('用户注册', ['user_id' => $user->id, 'email' => $user->email, 'ip' => $request->ip()]);

        return response()->json([
            'message' => '注册成功，请查收验证邮件后登录',
            'email' => $user->email,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = $credentials['email'];

        // Login throttling: max 5 failures per 5 minutes per email+IP
        $throttleKey = 'login:' . $email . ':' . $request->ip();
        $ipKey = 'login_ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('登录被锁定', ['ip' => $request->ip()]);
            return response()->json(['message' => "登录尝试次数过多，请{$seconds}秒后再试"], 429);
        }

        // Per-IP limit: max 20 failures per 5 minutes regardless of email
        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            Log::warning('IP登录被锁定', ['ip' => $request->ip()]);
            return response()->json(['message' => '登录尝试次数过多，请稍后再试'], 429);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300); // 5 minutes decay
            RateLimiter::hit($ipKey, 300);
            Log::info('登录失败', ['ip' => $request->ip()]);
            return response()->json(['message' => '邮箱或密码错误'], 422);
        }

        // Clear throttle on success
        RateLimiter::clear($throttleKey);
        RateLimiter::clear($ipKey);

        $user = $request->user();

        // Check email verification
        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            return response()->json([
                'message' => '请先验证邮箱后再登录',
                'unverified' => true,
                'email' => $user->email,
            ], 403);
        }

        $request->session()->regenerate();

        Log::info('登录成功', ['user_id' => $user->id, 'email' => $user->email, 'ip' => $request->ip()]);

        return response()->json($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            Log::info('退出登录', ['user_id' => $user->id, 'ip' => $request->ip()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => '已退出登录']);
    }

    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return redirect('/?email-verified=false');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/?email-verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Log::info('邮箱验证成功', ['user_id' => $user->id, 'email' => $user->email, 'ip' => $request->ip()]);
        }

        return redirect('/?email-verified=true');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        // Anti-enumeration: always return the same generic message
        $genericMessage = '如果该邮箱已注册且未验证，验证邮件将发送到您的邮箱';

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->hasVerifiedEmail()) {
            return response()->json(['message' => $genericMessage]);
        }

        $user->sendEmailVerificationNotification();
        Log::info('重发验证邮件', ['email' => $user->email, 'ip' => $request->ip()]);

        return response()->json(['message' => $genericMessage]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        Log::info('密码重置请求', ['ip' => $request->ip()]);

        $status = PasswordFacade::broker()->sendResetLink($request->only('email'));

        // Always return the same message regardless of whether email exists (prevent enumeration)
        return response()->json(['message' => '如果该邮箱已注册，重置链接将发送到您的邮箱']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordFacade::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        if ($status === PasswordFacade::PASSWORD_RESET) {
            Log::info('密码重置成功', ['email' => $request->email, 'ip' => $request->ip()]);
            return response()->json(['message' => '密码重置成功，请使用新密码登录']);
        }

        Log::warning('密码重置失败', ['email' => $request->email, 'ip' => $request->ip()]);
        return response()->json(['message' => '重置链接无效或已过期'], 422);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'current_password' => 'required_with:password|string',
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
        ]);

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (!empty($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return response()->json(['message' => '当前密码不正确'], 422);
            }
            $user->password = $data['password'];
        }

        $user->save();

        Log::info('用户更新资料', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json($user->fresh());
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        $file = $request->file('avatar');

        // Verify actual image content (prevent MIME spoofing)
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            return response()->json(['message' => '文件不是有效的图片'], 422);
        }

        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            return response()->json(['message' => '不支持的图片格式'], 422);
        }

        $user = $request->user();

        // Delete old avatar (use raw DB value, not the accessor-transformed URL)
        $oldAvatar = $user->getRawOriginal('avatar');
        if ($oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        Log::info('用户上传头像', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json([
            'message' => '头像已更新',
            'avatar' => Storage::url($path),
        ]);
    }
}
