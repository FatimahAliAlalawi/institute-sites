<?php

namespace App\Support;

class Installer
{
    public static function lockFile(): string
    {
        return storage_path('app/installed');
    }

    public static function installed(): bool
    {
        static $installed = null;

        return $installed ??= is_file(static::lockFile());
    }
}
