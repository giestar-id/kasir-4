<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
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
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        if ($setting->is_encrypted && ! empty($setting->value)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                return $default;
            }
        }

        return $setting->value ?? $default;
    }

    public static function set(string $key, mixed $value, bool $encrypt = false): static
    {
        $storedValue = $value;
        if ($encrypt && ! empty($value)) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $encrypt,
            ]
        );
    }
}
