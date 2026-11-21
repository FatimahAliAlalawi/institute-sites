<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * الدورات المسجلة بنظام "درس يوميًا":
 * - في أيام الدراسة فقط (عامة من الإعدادات، أو خاصة بكل متدرب) يُفتح للمتدرب عدد محدد من الدروس الجديدة.
 * - إكمال درس اليوم = حضور، ويوم دراسة بدون إكمال = غياب.
 */
class DailyRelease
{
    public const DAYS = [0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];

    public const DEFAULT_DAYS = [0, 1, 2, 3, 4];

    /** "0,1,2" ← [0,1,2] (يتجاهل القيم غير الصالحة) */
    public static function parse(?string $value): array
    {
        $days = array_values(array_unique(array_filter(
            array_map('intval', array_filter(explode(',', (string) $value), fn ($d) => trim($d) !== '')),
            fn ($d) => $d >= 0 && $d <= 6
        )));
        sort($days);

        return $days;
    }

    public static function globalDays(): array
    {
        return static::parse(setting('study_days')) ?: self::DEFAULT_DAYS;
    }

    public static function daysFor(?User $user): array
    {
        return ($user && ($own = static::parse($user->study_days))) ? $own : static::globalDays();
    }

    public static function label(array $days): string
    {
        return implode('، ', array_map(fn ($d) => self::DAYS[$d], $days));
    }

    public static function isStudyDay(?User $user, Carbon $date): bool
    {
        return in_array($date->dayOfWeek, static::daysFor($user), true);
    }

    public static function nextStudyDay(?User $user, ?Carbon $after = null): Carbon
    {
        $date = ($after ?? now())->copy()->startOfDay();
        for ($i = 1; $i <= 7; $i++) {
            $date->addDay();
            if (static::isStudyDay($user, $date)) {
                return $date;
            }
        }

        return $date;
    }

    /** صيغة "يوم الأحد 2 نوفمبر" أو "غدًا" */
    public static function humanDay(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'اليوم';
        }
        if ($date->isTomorrow()) {
            return 'غدًا ('.self::DAYS[$date->dayOfWeek].')';
        }

        return 'يوم '.self::DAYS[$date->dayOfWeek].' '.$date->translatedFormat('j F');
    }

    /**
     * حالة المتدرب في الدورة اليوم.
     *
     * @return array{enabled:bool,study_day:bool,per_day:int,used_today:int,remaining:int,unlocked:array,next_day:?Carbon,days:array}
     */
    public static function state(Course $course, ?User $user, ?Collection $items = null, ?array $progress = null): array
    {
        $items ??= $course->flatItems();
        $base = ['enabled' => false, 'study_day' => true, 'per_day' => 0, 'used_today' => 0, 'remaining' => 0, 'next_day' => null, 'days' => static::daysFor($user)];

        if (! $course->daily_release || ! $user) {
            return $base + ['unlocked' => static::allKeys($items)];
        }

        $progress ??= $course->progressFor($user);
        $doneLessons = array_map('intval', $progress['completed_lessons']);
        $today = now();
        $studyDay = static::isStudyDay($user, $today);
        $perDay = max(1, (int) $course->lessons_per_day);
        $usedToday = LessonCompletion::where('user_id', $user->id)->where('course_id', $course->id)
            ->where('created_at', '>=', $today->copy()->startOfDay())->count();
        $slots = $studyDay ? max(0, $perDay - $usedToday) : 0;

        $unlocked = [];
        $blocked = false;      // نفدت دروس اليوم: كل ما بعده مقفل
        $pendingBefore = false; // يوجد درس مفتوح لم يُكمل بعد قبل هذا العنصر
        foreach ($items as $item) {
            $model = $item['model'];
            $key = $item['type'].':'.$model->id;
            if ($item['type'] === 'live') {
                $unlocked[] = $key; // المحاضرة المباشرة متاحة لكل المشتركين في موعدها
            } elseif ($item['type'] === 'lesson') {
                if (in_array((int) $model->id, $doneLessons, true)) {
                    $unlocked[] = $key;
                } elseif (! $blocked && $slots > 0) {
                    $unlocked[] = $key;
                    $slots--;
                    $pendingBefore = true;
                } else {
                    $blocked = true; // درس المعاينة مفتوح للزوار فقط؛ المشترك يتبع جدوله
                }
            } elseif (! $blocked && ! $pendingBefore) {
                // الاختبار يُفتح بمجرد إكمال كل الدروس التي قبله
                $unlocked[] = $key;
            }
        }

        return [
            'enabled' => true,
            'study_day' => $studyDay,
            'per_day' => $perDay,
            'used_today' => $usedToday,
            'remaining' => $studyDay ? max(0, $perDay - $usedToday) : 0,
            'unlocked' => $unlocked,
            'next_day' => static::nextStudyDay($user),
            'days' => static::daysFor($user),
        ];
    }

    public static function isUnlocked(array $state, string $type, int $id): bool
    {
        return in_array($type.':'.$id, $state['unlocked'], true);
    }

    protected static function allKeys(Collection $items): array
    {
        return $items->map(fn ($i) => $i['type'].':'.$i['model']->id)->all();
    }

    // ================= التحضير =================

    /**
     * سجل الحضور لاشتراك في دورة يومية خلال فترة.
     * القيم: present (حضر) | absent (غاب) | off (ليس يوم دراسة) | today (اليوم ولم يحضر بعد) | none (خارج فترة الاشتراك)
     *
     * @return array<string,string> [Y-m-d => status]
     */
    public static function attendance(Enrollment $enrollment, Carbon $from, Carbon $to, ?array $presentDates = null): array
    {
        $user = $enrollment->user;
        $start = ($enrollment->approved_at ?? $enrollment->created_at)->copy()->startOfDay();
        $end = $enrollment->completed_at ? $enrollment->completed_at->copy()->startOfDay() : now()->startOfDay();
        $presentDates ??= static::presentDates($enrollment);

        $result = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $result[$key] = match (true) {
                isset($presentDates[$key]) => 'present',
                $d->lt($start) || $d->gt($end) || $d->isFuture() => 'none',
                ! static::isStudyDay($user, $d) => 'off',
                $d->isToday() => 'today',
                default => 'absent',
            };
        }

        return $result;
    }

    /** أيام الحضور (التي أكمل فيها المتدرب درسًا واحدًا على الأقل) */
    public static function presentDates(Enrollment $enrollment): array
    {
        return LessonCompletion::where('user_id', $enrollment->user_id)->where('course_id', $enrollment->course_id)
            ->pluck('created_at')->mapWithKeys(fn ($date) => [Carbon::parse($date)->format('Y-m-d') => true])->all();
    }

    /** ملخص الحضور منذ بداية الاشتراك */
    public static function summary(Enrollment $enrollment): array
    {
        $start = ($enrollment->approved_at ?? $enrollment->created_at)->copy();
        $records = static::attendance($enrollment, $start, now());
        $present = count(array_filter($records, fn ($s) => $s === 'present'));
        $absent = count(array_filter($records, fn ($s) => $s === 'absent'));

        return [
            'present' => $present,
            'absent' => $absent,
            'rate' => ($present + $absent) ? (int) round($present * 100 / ($present + $absent)) : 100,
        ];
    }
}
