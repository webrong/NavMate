<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, remove duplicate URLs keeping the latest one
        $duplicates = \DB::table('sites')
            ->select('url', \DB::raw('COUNT(*) as cnt'))
            ->groupBy('url')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $ids = \DB::table('sites')
                ->where('url', $dup->url)
                ->orderByDesc('id')
                ->skip(1)
                ->pluck('id');
            \DB::table('sites')->whereIn('id', $ids)->delete();
        }

        Schema::table('sites', function (Blueprint $table) {
            $table->unique('url');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropUnique(['url']);
        });
    }
};
