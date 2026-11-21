<?php

namespace App\Support;

use App\Models\Setting;

/** أقسام الصفحة الرئيسية: الترتيب والإظهار وعناوين الأقسام (من لوحة التحكم ← الصفحة الرئيسية) */
class HomeSections
{
    public const SECTIONS = [
        'hero' => 'الواجهة الرئيسية (العنوان والأزرار)',
        'stats' => 'الإحصائيات',
        'about' => 'نبذة من نحن',
        'courses' => 'الدورات المميزة',
        'programs' => 'البرامج المميزة',
        'journey' => 'رحلة المتدرب',
        'news' => 'آخر الأخبار',
        'partners' => 'الشركاء',
        'faqs' => 'الأسئلة الشائعة',
        'cta' => 'شريط الدعوة للتسجيل',
    ];

    /** الأقسام التي لها عنوان قابل للتعديل */
    public const TITLED = ['about', 'courses', 'programs', 'journey', 'news', 'partners', 'faqs'];

    /** @return array<int,array{key:string,on:bool,page_id?:int,style?:string,show_title?:bool}> */
    public static function all(): array
    {
        $saved = json_decode((string) setting('home_sections'), true);
        $list = is_array($saved) ? array_values(array_filter($saved, fn ($s) => isset($s['key']) && (isset(self::SECTIONS[$s['key']]) || $s['key'] === 'page'))) : [];

        // أي قسم أساسي غير موجود في المحفوظ يُضاف في آخره (مثلًا بعد تحديث يضيف قسمًا جديدًا)
        $present = array_column($list, 'key');
        foreach (array_keys(self::SECTIONS) as $key) {
            if (! in_array($key, $present, true)) {
                $list[] = ['key' => $key, 'on' => true];
            }
        }

        return $list;
    }

    public static function visible(): array
    {
        return array_values(array_filter(static::all(), fn ($s) => ! empty($s['on'])));
    }

    public static function save(array $sections): void
    {
        Setting::put(['home_sections' => json_encode(array_values($sections), JSON_UNESCAPED_UNICODE)]);
    }

    public static function text(string $section, string $field, string $default): string
    {
        return (string) setting('home_'.$section.'_'.$field, $default);
    }

    public static function label(array $section): string
    {
        if ($section['key'] === 'page') {
            return 'محتوى صفحة: '.(\App\Models\Page::find($section['page_id'] ?? 0)?->title ?? '(محذوفة)');
        }

        return self::SECTIONS[$section['key']] ?? $section['key'];
    }
}
