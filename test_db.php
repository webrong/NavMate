<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $categoryCount = \App\Models\Category::count();
    $siteCount = \App\Models\Site::count();
    $parentCount = \App\Models\Category::whereNull('parent_id')->count();
    $childCount = \App\Models\Category::whereNotNull('parent_id')->count();

    echo "Categories: $categoryCount\n";
    echo "Sites: $siteCount\n";
    echo "Parent cats: $parentCount\n";
    echo "Child cats: $childCount\n";

    echo "\nSample categories:\n";
    \App\Models\Category::take(5)->get(['id', 'name', 'parent_id', 'is_active'])->each(function($c) {
        echo "ID: {$c->id} | Name: {$c->name} | Parent: " . ($c->parent_id ?? 'NULL') . " | Active: {$c->is_active}\n";
    });

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
