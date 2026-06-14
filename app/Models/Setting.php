<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [];

    private static string $cacheKey = 'settings:all';

    /**
     * Get a setting value by key (cached)
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::allCached()->get($key, $default);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
        static::flushCache();
    }

    /**
     * Set multiple settings at once
     */
    public static function setMany(array $settings): void
    {
        $now = now();
        foreach ($settings as $key => $value) {
            static::query()->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now],
            );
        }
        static::flushCache();
    }

    /**
     * Get all settings as a key-value collection (cached)
     */
    public static function allCached(): Collection
    {
        try {
            $cached = Cache::get(static::$cacheKey);
            if ($cached instanceof Collection) {
                return $cached;
            }
        } catch (\Throwable) {
            // Cache corruption — clear and re-fetch
        }

        try {
            $result = static::query()->pluck('value', 'key');
            Cache::put(static::$cacheKey, $result, 3600);

            return $result;
        } catch (\Throwable) {
            // Database not available (e.g. during installation)
            return collect();
        }
    }

    /**
     * Flush the settings cache
     */
    public static function flushCache(): void
    {
        Cache::forget(static::$cacheKey);
    }
}
