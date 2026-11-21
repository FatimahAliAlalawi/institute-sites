<?php

namespace App\Support;

/**
 * إعداد البريد من لوحة التحكم (الإعدادات ← البريد الإلكتروني) بدل ملف .env:
 * يكفي إنشاء بريد في cPanel مثل info@albsaer.com وكتابة بياناته هنا.
 */
class MailSettings
{
    public static function configured(): bool
    {
        return filled(setting('mail_host')) && filled(setting('mail_username')) && filled(setting('mail_password'));
    }

    public static function apply(): void
    {
        if (! Installer::installed() || ! static::configured()) {
            return;
        }

        $port = (int) (setting('mail_port') ?: 465);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => setting('mail_host'),
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => setting('mail_username'),
            'mail.mailers.smtp.password' => setting('mail_password'),
            'mail.from.address' => setting('mail_from_address') ?: setting('mail_username'),
            'mail.from.name' => setting('mail_from_name') ?: setting('site_name', config('app.name')),
        ]);
    }
}
