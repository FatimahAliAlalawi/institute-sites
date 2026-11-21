<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected static ?array $loaded = null;

    public static function allCached(): array
    {
        return static::$loaded ??= Cache::rememberForever('site_settings', fn () => static::pluck('value', 'key')->all());
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        static::flush();
    }

    public static function flush(): void
    {
        Cache::forget('site_settings');
        static::$loaded = null;
    }
}
