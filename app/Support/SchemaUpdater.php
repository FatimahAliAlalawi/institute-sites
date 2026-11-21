<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;

/**
 * تحديث قاعدة البيانات تلقائيًا بعد رفع نسخة جديدة من الموقع:
 * عند أول زيارة بعد التحديث تُنفَّذ أي جداول/أعمدة جديدة مرة واحدة فقط (بدون إعادة تثبيت أو فقدان بيانات).
 */
class SchemaUpdater
{
    public static function latestMigration(): string
    {
        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files);

        return $files ? basename(end($files)) : '';
    }

    public static function markerFile(): string
    {
        return storage_path('app/schema_version');
    }

    public static function markCurrent(): void
    {
        @file_put_contents(static::markerFile(), static::latestMigration());
    }

    public static function ensureUpToDate(): void
    {
        $latest = static::latestMigration();
        if ($latest === '' || @file_get_contents(static::markerFile()) === $latest) {
            return;
        }

        $lock = @fopen(storage_path('app/schema.lock'), 'c');
        if (! $lock || ! flock($lock, LOCK_EX)) {
            return;
        }
        try {
            if (@file_get_contents(static::markerFile()) !== $latest) {
                @set_time_limit(300);
                Artisan::call('migrate', ['--force' => true]);
                static::markCurrent();
            }
        } catch (\Throwable $e) {
            report($e);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
