<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /**
     * In-memory request cache to prevent duplicate queries in the same cycle.
     */
    protected static array $runtimeCache = [];

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key] ?? $default;
        }

        $value = Cache::remember("app_setting_{$key}", 86400, function () use ($key) {
            $setting = static::where('key', $key)->first();
            if (! $setting) {
                return null;
            }

            if ($setting->is_encrypted && ! empty($setting->value)) {
                try {
                    return Crypt::decryptString($setting->value);
                } catch (\Exception $e) {
                    return null;
                }
            }

            return $setting->value;
        });

        static::$runtimeCache[$key] = $value;

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value, bool $encrypt = false): static
    {
        $storedValue = $value;
        if ($encrypt && ! empty($value)) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $encrypt,
            ]
        );

        Cache::forget("app_setting_{$key}");
        static::$runtimeCache[$key] = $value;

        return $setting;
    }

    public static function clearSettingCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("app_setting_{$key}");
            unset(static::$runtimeCache[$key]);
        } else {
            static::$runtimeCache = [];
        }
    }
}
