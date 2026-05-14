<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes for high-frequency queries.
     *
     * - click_logs: analytics queries filter/group by clicked_at and site_id
     * - sites: frontend listing filters by is_active + is_public, sorted by sort_order
     * - categories: tree queries filter by parent_id + is_active, sorted by sort_order
     */
    public function up(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->index('clicked_at', 'idx_click_logs_clicked_at');
            $table->index(['site_id', 'clicked_at'], 'idx_click_logs_site_clicked');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->index(['is_active', 'is_public', 'sort_order'], 'idx_sites_active_public_sort');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['parent_id', 'is_active', 'sort_order'], 'idx_categories_parent_active_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('click_logs', function (Blueprint $table) {
            $table->dropIndex('idx_click_logs_clicked_at');
            $table->dropIndex('idx_click_logs_site_clicked');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex('idx_sites_active_public_sort');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_parent_active_sort');
        });
    }
};
