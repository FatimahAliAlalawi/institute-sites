<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * المزامنة التلقائية مع الموقع القديم (ووردبريس + Tutor LMS):
 * - كل بضع دقائق: الدورات والدروس والفيديوهات الجديدة، الحسابات الجديدة، الاشتراكات.
 * - فورًا: عند دخول الطالب أو فتحه لوحته تُجلب اشتراكاته من الموقع القديم.
 * بيانات الاتصال تُحفظ مشفّرة في الإعدادات ولا تُعرض كلمة المرور مرة أخرى.
 */
class TutorSync
{
    public const DEFAULT_INTERVAL = 5;

    /** الميزة مفعّلة لهذا المعهد فقط (config/institute.php) */
    public static function available(): bool
    {
        return (bool) config('institute.tutor_sync', false);
    }

    public static function config(): ?array
    {
        $raw = setting('tutor_sync_connection');
        if (! $raw) {
            return null;
        }
        try {
            return json_decode(Crypt::decryptString($raw), true) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function saveConfig(array $config): void
    {
        Setting::put(['tutor_sync_connection' => Crypt::encryptString(json_encode($config))]);
    }

    public static function enabled(): bool
    {
        return self::available() && Installer::installed() && setting('tutor_sync_enabled') === '1' && self::config();
    }

    public static function interval(): int
    {
        return max(1, min(1440, (int) setting('tutor_sync_interval', self::DEFAULT_INTERVAL)));
    }

    /** رمز رابط المهمة المجدولة (cron) — يُنشأ مرة واحدة */
    public static function token(): string
    {
        if (! ($token = setting('tutor_sync_token'))) {
            Setting::put(['tutor_sync_token' => $token = Str::random(40)]);
        }

        return $token;
    }

    public static function importer(?array $config = null): TutorImporter
    {
        $c = $config ?? self::config() ?? throw new \RuntimeException('لم تُحفظ بيانات اتصال الموقع القديم.');

        return new TutorImporter(
            ['driver' => $c['driver'] ?? 'mysql', 'host' => $c['host'], 'port' => $c['port'], 'database' => $c['database'], 'username' => $c['username'], 'password' => $c['password'] ?? ''],
            ['prefix' => $c['prefix'] ?? 'wp_', 'uploads_path' => $c['uploads_path'] ?? null, 'site_url' => $c['site_url'] ?? null]
        );
    }

    /** مزامنة كاملة (مع قفل حتى لا تعمل نسختان معًا). يرجع التقرير أو null إن كانت مزامنة أخرى جارية */
    public static function run(string $trigger): ?array
    {
        $lock = Cache::lock('tutor_sync_running', 900);
        if (! $lock->get()) {
            return null;
        }
        try {
            $report = self::importer()->sync();
            $status = 'ok';
        } catch (\Throwable $e) {
            report($e);
            $report = ['counts' => [], 'warnings' => [$e->getMessage()]];
            $status = 'error';
        } finally {
            $lock->release();
        }

        Setting::put([
            'tutor_sync_last_at' => now()->toDateTimeString(),
            'tutor_sync_last_report' => json_encode($report + ['status' => $status, 'trigger' => $trigger], JSON_UNESCAPED_UNICODE),
        ]);

        return $report + ['status' => $status];
    }

    /** تُستدعى بعد إرسال الصفحة للزائر: تُشغّل المزامنة إن مرّت المدة المحددة (بدون الحاجة لـ cron) */
    public static function autoRun(): void
    {
        if (self::enabled() && Cache::add('tutor_sync_tick', 1, self::interval() * 60)) {
            self::run('auto');
        }
    }

    /** جلب طالب واحد واشتراكاته فورًا (مرة كل دقيقة على الأكثر لنفس البريد) */
    public static function forEmail(string $email): void
    {
        $email = mb_strtolower(trim($email));
        if (! self::enabled() || ! Cache::add('tutor_sync_email:'.sha1($email), 1, 60)) {
            return;
        }
        try {
            self::importer()->syncEmail($email);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function lastReport(): ?array
    {
        return json_decode((string) setting('tutor_sync_last_report'), true) ?: null;
    }
}
