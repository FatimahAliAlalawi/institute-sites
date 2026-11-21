<?php

use App\Models\Setting;
use App\Support\Installer;

if (! function_exists('setting')) {
    /** قراءة إعداد من لوحة التحكم (الإعدادات ← ...) مع قيمة افتراضية */
    function setting(string $key, $default = null)
    {
        if (! Installer::installed()) {
            return $default;
        }

        $value = Setting::allCached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }
}

if (! function_exists('media_url')) {
    /** رابط صورة مرفوعة (مسار نسبي داخل public) أو رابط خارجي، أو null */
    function media_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : asset($path);
    }
}

if (! function_exists('lines')) {
    /** تحويل نص متعدد الأسطر إلى مصفوفة (يتجاهل الأسطر الفارغة) */
    function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }
}
