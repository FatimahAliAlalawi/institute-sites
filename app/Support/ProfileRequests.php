<?php

namespace App\Support;

use App\Models\Registration;

/**
 * رسالة «أكمل بياناتك» تُرسل تلقائيًا بالإيميل بعد الدفع (حالة «مقبول»)، مرة واحدة لكل متدرب.
 * من ليس عنده بريد تظهر رسالته جاهزة للواتساب في «تشغيل الدورة» وصفحة الطلب.
 */
class ProfileRequests
{
    public static function send(Registration $r): bool
    {
        if ($r->profile_done_at || $r->stepDone('profile') || ! filled($r->email) || ! $r->program
            || ! in_array($r->status, Registration::SEAT_STATUSES, true) || ! MailSettings::configured()) {
            return false;
        }
        try {
            TraineeMessages::email('profile', $r, $r->program);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
        $steps = $r->steps ?? [];
        $steps['profile'] = ['at' => now()->format('Y-m-d H:i'), 'by' => 'إيميل تلقائي'];
        $r->forceFill(['steps' => $steps])->save();

        return true;
    }

    /** لكل من دفع ولم تصله الرسالة (مثل المقبولين من السحب أو قبل تفعيل البريد) */
    public static function sweep(): int
    {
        $n = 0;
        Registration::with('program')->whereIn('status', Registration::SEAT_STATUSES)
            ->whereNull('profile_done_at')->whereNotNull('email')->where('email', '!=', '')
            ->where('updated_at', '>=', now()->subDays(30))
            ->limit(100)->get()
            ->each(function (Registration $r) use (&$n) {
                $n += static::send($r) ? 1 : 0;
            });

        return $n;
    }
}
