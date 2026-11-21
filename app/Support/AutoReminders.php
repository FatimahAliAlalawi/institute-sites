<?php

namespace App\Support;

use App\Models\Program;
use Illuminate\Support\Facades\Cache;

/**
 * التذكير التلقائي بالإيميل (الخطوتان 07 و 08 في آلية التسجيل):
 * - قبل الدورة بيوم: بعد الساعة 4 العصر.
 * - صباح يوم الدورة: من 7 صباحًا حتى 12 الظهر.
 * يُرسل لمن عنده بريد ولم تُنفّذ له الخطوة، ويؤشّرها. يعمل مع زيارات الموقع (كل 15 دقيقة) أو من cron.
 */
class AutoReminders
{
    public static function enabled(): bool
    {
        return Installer::installed() && setting('auto_reminders') === '1' && MailSettings::configured();
    }

    /** للتشغيل بعد الطلبات: مرة كل 15 دقيقة كحد أقصى */
    public static function autoRun(): void
    {
        if (! static::enabled() || ! Cache::add('auto_reminders_tick', 1, now()->addMinutes(15))) {
            return;
        }
        static::run();
    }

    /** يرجع عدد الرسائل المرسلة */
    public static function run(): int
    {
        if (! static::enabled()) {
            return 0;
        }

        $now = now();
        $jobs = [];
        if ($now->hour >= 16) {
            $jobs['reminder_1d'] = $now->copy()->addDay()->toDateString();
        }
        if ($now->hour >= 7 && $now->hour < 12) {
            $jobs['reminder_am'] = $now->toDateString();
        }

        $sent = 0;
        foreach ($jobs as $type => $date) {
            $programs = Program::whereDate('start_date', $date)->get();
            foreach ($programs as $program) {
                foreach ($program->seatRegistrations()->get() as $t) {
                    if ($t->stepDone($type) || ! filled($t->email)) {
                        continue;
                    }
                    try {
                        TraineeMessages::email($type, $t, $program);
                        $steps = $t->steps ?? [];
                        $steps[$type] = ['at' => now()->format('Y-m-d H:i'), 'by' => 'إيميل تلقائي'];
                        $t->forceFill(['steps' => $steps])->save();
                        $sent++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        return $sent;
    }
}
