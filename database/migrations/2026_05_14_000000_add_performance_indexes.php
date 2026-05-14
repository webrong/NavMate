<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->index(['site_id', 'clicked_at'], 'click_logs_site_time_idx');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->index(['is_active', 'is_public', 'sort_order'], 'sites_list_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['parent_id', 'is_active', 'sort_order'], 'categories_tree_idx');
        });
    }

    public function down(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->dropIndex('click_logs_site_time_idx');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex('sites_list_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_tree_idx');
        });
    }
};
