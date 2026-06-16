<?php

use App\Models\AdminUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Create default admin user (only when NOT running through installer)
        if (! env('NAV_INSTALLING')) {
            $password = env('ADMIN_DEFAULT_PASSWORD', str()->random(16));
            AdminUser::create([
                'name' => 'Admin',
                'username' => 'admin',
                'email' => env('ADMIN_DEFAULT_EMAIL', 'admin@localhost'),
                'password' => $password,
                'email_verified_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
