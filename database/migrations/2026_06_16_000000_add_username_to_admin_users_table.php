<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add a username column to admin_users and backfill it from the existing
     * name column. This migrates the admin login from email-based to
     * username-based (more practical for a small admin team).
     */
    public function up(): void
    {
        // Fresh installs already have username (added to create_admin_users
        // migration in v1.3.4). Only existing installs upgrading from older
        // versions need this migration — guard with a column check.
        if (Schema::hasColumn('admin_users', 'username')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table) {
            // Nullable during migration so existing rows can be backfilled
            // before the unique index is enforced.
            $table->string('username')->nullable()->after('name');
        });

        // Backfill: derive username from name (lowercase, slug-style).
        $admins = DB::table('admin_users')->whereNull('username')->get();
        foreach ($admins as $admin) {
            $base = Str::slug($admin->name) ?: 'admin';
            $username = $base;
            $i = 1;
            // Ensure uniqueness against existing usernames.
            while (DB::table('admin_users')->where('username', $username)->exists()) {
                $username = $base.$i++;
            }
            DB::table('admin_users')->where('id', $admin->id)->update(['username' => $username]);
        }

        // Now enforce uniqueness + not-null.
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        // Only drop if this migration actually added it (not a fresh install
        // where username comes from create_admin_users_table).
        // We can't easily tell, so we skip rollback — username is now an
        // integral column and dropping it would break login entirely.
    }
};
