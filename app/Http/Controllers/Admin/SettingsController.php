<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    private const SETTINGS_KEYS = [
        'site_name', 'site_description', 'site_keywords', 'site_logo',
        'footer_text', 'icp_number', 'enable_register', 'maintenance_mode',
        'announcement', 'qrcode_1_image', 'qrcode_1_label', 'qrcode_2_image', 'qrcode_2_label',
        'about_description', 'about_timeline', 'terms_content',
        'contact_email', 'contact_qq', 'contact_wechat',
        'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
        'mail_from_address', 'mail_from_name',
        'baidu_verify', 'google_verify', 'bing_verify',
    ];

    private const PUBLIC_KEYS = [
        'site_name', 'site_description', 'site_logo', 'footer_text',
        'icp_number', 'enable_register', 'maintenance_mode', 'announcement',
        'qrcode_1_image', 'qrcode_1_label', 'qrcode_2_image', 'qrcode_2_label',
        'about_description', 'about_timeline', 'terms_content',
        'contact_email', 'contact_qq', 'contact_wechat',
        'baidu_verify', 'google_verify', 'bing_verify',
    ];

    private const BOOL_KEYS = ['enable_register', 'maintenance_mode'];

    private const ENCRYPTED_KEYS = ['mail_password'];

    /**
     * Public settings for frontend (safe subset)
     */
    public function publicSettings(): JsonResponse
    {
        $allSettings = Setting::allCached();
        $settings = [];
        foreach (self::PUBLIC_KEYS as $key) {
            $val = $allSettings->get($key);
            if (in_array($key, self::BOOL_KEYS)) {
                $val = $val === '1';
            }
            $settings[$key] = $val;
        }

        $settings['site_name'] = $settings['site_name'] ?: config('app.name', '导航');

        return response()->json($settings);
    }

    public function index(): JsonResponse
    {
        $allSettings = Setting::allCached();
        $settings = [];
        foreach (self::SETTINGS_KEYS as $key) {
            $val = $allSettings->get($key);
            if (in_array($key, self::BOOL_KEYS)) {
                $val = $val === '1';
            }
            if (in_array($key, self::ENCRYPTED_KEYS) && $val) {
                try {
                    $val = Crypt::decryptString($val);
                } catch (\Throwable) {
                    $val = '';
                }
            }
            $settings[$key] = $val;
        }

        $settings['site_name'] = $settings['site_name'] ?: config('app.name', '导航');

        return response()->json(['code' => 0, 'data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_name' => 'nullable|string|max:100',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string|max:500',
            'site_logo' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:500',
            'icp_number' => 'nullable|string|max:100',
            'enable_register' => 'nullable|boolean',
            'maintenance_mode' => 'nullable|boolean',
            'announcement' => 'nullable|string|max:1000',
            'qrcode_1_image' => 'nullable|string|max:500',
            'qrcode_1_label' => 'nullable|string|max:100',
            'qrcode_2_image' => 'nullable|string|max:500',
            'qrcode_2_label' => 'nullable|string|max:100',
            'about_description' => 'nullable|string|max:500',
            'about_timeline' => 'nullable|string|max:5000',
            'terms_content' => 'nullable|string|max:10000',
            'contact_email' => 'nullable|string|max:100',
            'contact_qq' => 'nullable|string|max:20',
            'contact_wechat' => 'nullable|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|between:1,65535',
            'mail_encryption' => 'nullable|string|in:ssl,tls,null',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'baidu_verify' => 'nullable|string|max:100',
            'google_verify' => 'nullable|string|max:100',
            'bing_verify' => 'nullable|string|max:100',
        ]);

        foreach ($data as $key => $value) {
            if (in_array($key, self::BOOL_KEYS)) {
                $value = $value ? '1' : '0';
            }
            if (in_array($key, self::ENCRYPTED_KEYS) && $value !== null && $value !== '') {
                $value = Crypt::encryptString($value);
            }
            Setting::set($key, $value);
        }

        return response()->json(['code' => 0, 'msg' => '设置已保存']);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $file = $request->file('image');
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return response()->json(['code' => 1, 'msg' => '文件不是有效的图片'], 422);
        }

        $path = $file->store('settings', 'public');

        return response()->json([
            'code' => 0,
            'msg' => '上传成功',
            'data' => ['url' => Storage::url($path)],
        ]);
    }

    /**
     * Send a test email using the current or provided mail settings
     */
    public function testEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to' => 'required|email',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|between:1,65535',
            'mail_encryption' => 'required|string|in:ssl,tls,null',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'required|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        // SSRF protection: block internal/private SMTP hosts
        $smtpHost = $data['mail_host'];
        $resolvedIp = gethostbyname($smtpHost);
        if ($resolvedIp !== $smtpHost && \App\Services\UrlFetcherService::isInternalIp($resolvedIp)) {
            return response()->json(['code' => 1, 'msg' => 'SMTP 服务器地址不允许指向内网'], 422);
        }

        // Temporarily override mail config
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $data['mail_host'],
                'port' => $data['mail_port'],
                'encryption' => $data['mail_encryption'] === 'null' ? null : $data['mail_encryption'],
                'username' => $data['mail_username'],
                'password' => $data['mail_password'],
                'timeout' => 10,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
            'mail.from' => [
                'address' => $data['mail_from_address'],
                'name' => $data['mail_from_name'] ?: config('app.name'),
            ],
        ]);

        try {
            Mail::raw('这是一封来自导航站系统设置的测试邮件，如果您收到了此邮件，说明邮件配置正确。', function ($message) use ($data) {
                $message->to($data['to'])
                    ->subject('邮件配置测试 - ' . config('app.name'));
            });

            Log::info('测试邮件发送成功', ['to' => $data['to'], 'host' => $data['mail_host']]);

            return response()->json(['code' => 0, 'msg' => '测试邮件已发送，请检查收件箱']);
        } catch (\Throwable $e) {
            Log::warning('测试邮件发送失败', ['to' => $data['to'], 'error' => $e->getMessage()]);

            return response()->json([
                'code' => 1,
                'msg' => '发送失败：' . $e->getMessage(),
            ], 422);
        }
    }
}
