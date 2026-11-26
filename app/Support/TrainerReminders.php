<?php

namespace App\Support;

use App\Mail\TraineeMessage;
use App\Models\Admin;
use App\Models\Program;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * تذكير المدرب بالإيميل: صباحًا (من 7) بمهام اليوم والمتأخرة، ومساءً (من 7 مساءً) إذا لم يُحضَّر يوم الدورة.
 * رسالة واحدة لكل مدرب في كل مرة، ولا تتكرر في نفس اليوم.
 */
class TrainerReminders
{
    public static function enabled(): bool
    {
        return Installer::installed() && setting('trainer_reminders', '1') !== '0' && MailSettings::configured();
    }

    public static function autoRun(): void
    {
        if (! static::enabled() || ! Cache::add('trainer_reminders_tick', 1, now()->addMinutes(15))) {
            return;
        }
        static::run();
    }

    public static function run(): int
    {
        if (! static::enabled()) {
            return 0;
        }
        $now = now();
        $slot = match (true) {
            $now->hour >= 19 => 'pm',
            $now->hour >= 7 && $now->hour < 12 => 'am',
            default => null,
        };
        if (! $slot) {
            return 0;
        }

        $programs = TaskBoard::activePrograms();
        $ids = $programs->pluck('trainer_admin_id')->filter()->unique();
        $sent = 0;
        foreach (Admin::with('role')->whereIn('id', $ids)->where('is_active', true)->get() as $admin) {
            if (! filled($admin->email)) {
                continue;
            }
            $tasks = TaskBoard::forAdmin($admin, $programs)->filter(fn ($t) => ! $t['done'] && ($t['today'] || $t['overdue'])
                && preg_match('/^(attendance-|file-|test-)/', $t['key']));
            if ($slot === 'pm') {
                $tasks = $tasks->filter(fn ($t) => $t['today'] && str_starts_with($t['key'], 'attendance-'));
            }
            if ($tasks->isEmpty() || ! Cache::add('trainer_mail_'.$admin->id.'_'.$now->toDateString().'_'.$slot, 1, now()->addDays(2))) {
                continue;
            }
            try {
                MailSettings::apply();
                Mail::to($admin->email, $admin->name)->send(new TraineeMessage(static::subject($slot, $tasks->count()), static::body($admin, $tasks, $slot)));
                $sent++;
            } catch (\Throwable $e) {
                Cache::forget('trainer_mail_'.$admin->id.'_'.$now->toDateString().'_'.$slot);
                report($e);
            }
        }

        return $sent;
    }

    protected static function subject(string $slot, int $n): string
    {
        return $slot === 'pm' ? 'تذكير: تحضير دورة اليوم لم يُسجَّل' : 'مهامك اليوم في '.setting('site_name', config('app.name')).' ('.$n.')';
    }

    protected static function body(Admin $admin, $tasks, string $slot): string
    {
        $lines = [($slot === 'pm' ? 'مساء الخير' : 'صباح الخير').' '.(preg_match('/^(أ|م|د)\./u', trim($admin->name)) ? '' : 'أ. ').trim($admin->name).'،', '', $slot === 'pm' ? 'لم يُسجَّل تحضير اليوم بعد:' : 'هذه مهامك المطلوبة:', ''];
        foreach ($tasks->groupBy(fn ($t) => $t['program']->id) as $list) {
            $lines[] = '■ '.$list->first()['program']->title;
            foreach ($list as $t) {
                $lines[] = '  - '.$t['title'].($t['overdue'] ? ' (متأخرة، موعدها '.$t['due']->format('Y-m-d').')' : '');
            }
            $lines[] = '';
        }
        $lines[] = 'افتح «مهامي»: '.route('admin.my-tasks');

        return implode("\n", $lines);
    }
}
