<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Configure password complexity requirements
        Password::defaults(function () {
            $rule = Password::min(8)->mixedCase()->numbers()->symbols();

            if (app()->environment('production')) {
                $rule->uncompromised();
            }

            return $rule;
        });
        // Share site settings with SPA views
        View::composer('spa', function ($view) {
            $settingsPath = storage_path('app/settings.json');
            $settings = File::exists($settingsPath)
                ? json_decode(File::get($settingsPath), true) ?? []
                : [];

            $view->with('settings', $settings);
        });

        // Password reset link → SPA frontend
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return config('app.url').'/?reset-password=true&token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // Load mail settings from database (override config)
        try {
            $mailHost = Setting::get('mail_host');
            if ($mailHost) {
                $mailPassword = Setting::get('mail_password');
                if ($mailPassword) {
                    try {
                        $mailPassword = Crypt::decryptString($mailPassword);
                    } catch (\Throwable) {
                        $mailPassword = '';
                    }
                }

                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $mailHost,
                    'mail.mailers.smtp.port' => (int) (Setting::get('mail_port') ?: 465),
                    'mail.mailers.smtp.encryption' => Setting::get('mail_encryption') ?: 'ssl',
                    'mail.mailers.smtp.username' => Setting::get('mail_username'),
                    'mail.mailers.smtp.password' => $mailPassword,
                ]);

                $fromAddress = Setting::get('mail_from_address');
                if ($fromAddress) {
                    config([
                        'mail.from.address' => $fromAddress,
                        'mail.from.name' => Setting::get('mail_from_name') ?: config('app.name'),
                    ]);
                }
            }
        } catch (\Throwable) {
            // Settings table may not exist yet (during migrations)
        }

        // Email verification link → backend verify endpoint → redirect to SPA
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }
}
