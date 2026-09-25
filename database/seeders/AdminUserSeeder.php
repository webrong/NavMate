<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        $generated = false;
        if (! $password) {
            // No hardcoded default: a seeded admin with a guessable password
            // is an immediate account takeover. Generate one and print it once.
            $password = Str::random(16);
            $generated = true;
        }

        $admin = AdminUser::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => env('ADMIN_EMAIL', 'admin@localhost'),
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        if ($generated && $admin->wasRecentlyCreated) {
            $this->command?->warn('已生成随机管理员密码: '.$password.'（仅显示这一次，请立即登录修改）');
        }
    }
}
