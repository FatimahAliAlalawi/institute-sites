<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** حفظ الصور المرفوعة داخل public/uploads/{folder} وإرجاع المسار النسبي */
class Uploader
{
    public static function store(UploadedFile $file, string $folder): string
    {
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $name = date('Ymd').'-'.Str::random(16).'.'.$ext;
        $file->move(public_path('uploads/'.$folder), $name);

        return 'uploads/'.$folder.'/'.$name;
    }

    /** يحذف الملف فقط إذا كان داخل مجلد الرفع (لا يحذف صور assets الافتراضية) */
    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/') && ! str_contains($path, '..')) {
            @unlink(public_path($path));
        }
        if ($path && str_starts_with($path, 'private/') && ! str_contains($path, '..')) {
            @unlink(storage_path('app/'.$path));
        }
    }

    /** ملفات خاصة (إيصالات التحويل ومرفقات الدروس) خارج public — تُنزَّل عبر التحقق من الصلاحية فقط */
    public static function storePrivate(UploadedFile $file, string $folder): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $name = date('Ymd').'-'.Str::random(20).'.'.preg_replace('/[^a-z0-9]/', '', $ext);
        $file->move(storage_path('app/private/'.$folder), $name);

        return 'private/'.$folder.'/'.$name;
    }

    public static function privatePath(string $path): ?string
    {
        $full = storage_path('app/'.$path);

        return str_starts_with($path, 'private/') && ! str_contains($path, '..') && is_file($full) ? $full : null;
    }

    /** يستبدل صورة قديمة بجديدة إذا رُفعت، أو يحذفها إذا طُلب الحذف */
    public static function replace(?UploadedFile $file, ?string $old, string $folder, bool $remove = false): ?string
    {
        if ($file) {
            static::delete($old);

            return static::store($file, $folder);
        }
        if ($remove) {
            static::delete($old);

            return null;
        }

        return $old;
    }
}
