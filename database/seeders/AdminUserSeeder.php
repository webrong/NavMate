<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => env('ADMIN_EMAIL', 'admin@localhost'),
                'password' => env('ADMIN_PASSWORD', 'admin123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
