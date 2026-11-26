<?php

namespace App\Support;

use App\Mail\TraineeMessage;
use App\Models\Program;
use App\Models\ProgramAttendance;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;

/**
 * رسائل المتدربين الثابتة (الدليل التشغيلي للأفراد): نص واحد يُرسل بالإيميل أو يُفتح جاهزًا في واتساب.
 * النصوص قابلة للتعديل من الإعدادات ← قوالب رسائل المتدربين.
 */
class TraineeMessages
{
    /** المفتاح => [اسم الرسالة، عنوان الإيميل] — المفاتيح الأربعة الأولى هي خطوات في «تشغيل الدورة» */
    public const TYPES = [
        'invoice_sent' => ['التأكيد مع الفاتورة ورابط المجموعة', 'تأكيد تسجيلك في {الدورة}'],
        'profile' => ['رابط إكمال البيانات', 'أكمل بياناتك للتسجيل في {الدورة}'],
        'welcome' => ['الترحيب', 'أهلًا بك في {الدورة}'],
        'reminder_1d' => ['التذكير قبل الدورة بيوم', 'تذكير: {الدورة} تبدأ غدًا'],
        'reminder_am' => ['تذكير صباح الدورة', 'اليوم أول أيام {الدورة}'],
        'attendance' => ['تقرير الحضور', 'تقرير حضورك في {الدورة}'],
        'pre_test' => ['رابط الاختبار القبلي', 'الاختبار القبلي: {الدورة}'],
        'post_test' => ['رابط الاختبار البعدي', 'الاختبار البعدي: {الدورة}'],
        'thanks' => ['الشكر بعد الدورة', 'شكرًا لإتمامك {الدورة}'],
    ];

    public const DEFAULTS = [
        'invoice_sent' => "أهلًا {الاسم} ✅\nتم تأكيد تسجيلك في دورة {الدورة} (رقم الاعتماد {رقم_الاعتماد}).\nرقم الفاتورة: {رقم_الفاتورة}\nتبدأ الدورة {التاريخ}، {الأيام} من {الوقت}.\nرابط مجموعة الدورة: {رابط_المجموعة}\nأضف أيام الدورة لتقويم جوالك: {رابط_التقويم}\nفضلًا أكمل بياناتك للشهادة: {رابط_إكمال_البيانات}\nنتشرف بك في {المعهد}.",
        'profile' => "أهلًا {الاسم} ✅\nتم تسجيلك في دورة {الدورة}.\nفضلًا أكمل بياناتك (الاسم الرباعي، الهوية، تاريخ الميلاد، الجنس، الجوال، البريد) من هذا الرابط، لأنها تُكتب في الشهادة:\n{رابط_إكمال_البيانات}\nالرابط خاص فيك.",
        'welcome' => "حيّاك الله {الاسم} 🌿\nسعداء بانضمامك لدورة {الدورة} مع {المدرب}.\nأي سؤال قبل الدورة تواصل معنا على {رقم_المعهد}.",
        'reminder_1d' => "تذكير 🔔 {الاسم}\nدورتك {الدورة} تبدأ غدًا {التاريخ} الساعة {الوقت}.\nالمكان: {المكان}\nفضلًا أكّد حضورك بالرد على هذه الرسالة.",
        'reminder_am' => "صباح الخير {الاسم} ☀️\nاليوم أول أيام دورة {الدورة}، الوقت {الوقت}.\nالمكان: {المكان}\nللتواصل: {رقم_المعهد}\nنشوفك على خير.",
        'attendance' => "مرحبًا {الاسم} 📋\nتقرير حضورك في دورة {الدورة} حتى اليوم:\nحضرت {أيام_الحضور} من {أيام_الدورة} أيام.\n{ملاحظة_الحضور}",
        'pre_test' => "أهلًا {الاسم} 📝\nقبل بداية دورة {الدورة} نحتاج تجيب على اختبار قصير لمعرفة مستواك الحالي (ليس فيه نجاح أو رسوب):\n{رابط_الاختبار_القبلي}\nالرابط خاص فيك والإرسال مرة واحدة.",
        'post_test' => "أهلًا {الاسم} 📝\nوصلنا لنهاية دورة {الدورة}. أجب على الاختبار البعدي لتشوف مقدار تقدّمك:\n{رابط_الاختبار_البعدي}\nالرابط خاص فيك والإرسال مرة واحدة.",
        'thanks' => "شكرًا {الاسم} على إتمام دورة {الدورة} 🎓\nرأيك يهمنا، ودوراتنا القادمة هنا: {رابط_الخطة}\nللتواصل: {رقم_المعهد}",
    ];

    public static function template(string $type): string
    {
        return (string) (setting('msg_'.$type) ?: (self::DEFAULTS[$type] ?? ''));
    }

    public static function render(string $type, Registration $r, Program $p): string
    {
        return strtr(self::template($type), self::vars($r, $p));
    }

    public static function subject(string $type, Program $p): string
    {
        return str_replace('{الدورة}', $p->title, self::TYPES[$type][1] ?? $p->title);
    }

    /** رسالة الاختبار تُرسل فقط والاختبار مفتوح؛ باقي الرسائل متاحة دائمًا */
    public static function available(string $type, Program $p): bool
    {
        return match ($type) {
            'pre_test' => $p->testOpen('pre'),
            'post_test' => $p->testOpen('post'),
            default => true,
        };
    }

    public static function vars(Registration $r, Program $p): array
    {
        $att = ProgramAttendance::summaryFor($r, $p);

        return [
            '{الاسم}' => trim(explode(' ', trim((string) $r->name))[0] ?: (string) $r->name),
            '{الاسم_الكامل}' => $r->name,
            '{الدورة}' => $p->title,
            '{رقم_الاعتماد}' => $p->accreditation_no ?: '—',
            '{التاريخ}' => $p->start_date?->translatedFormat('l j F Y') ?? '—',
            '{الأيام}' => $p->schedule_days ?: '—',
            '{الوقت}' => $p->schedule_time ?: '—',
            '{المدرب}' => $p->trainer_name ?: 'المدرب',
            '{المكان}' => $p->location ?: (setting('address') ?: '—'),
            '{رابط_المجموعة}' => $p->group_link ?: '—',
            '{رقم_الفاتورة}' => $r->invoice_no ?: '—',
            '{رقم_المعهد}' => $p->contact_number ?: '—',
            '{المعهد}' => setting('site_name', config('app.name')),
            '{رابط_الخطة}' => route('programs.plan'),
            '{رابط_التقويم}' => ($p->session_dates || $p->start_date) ? route('programs.ics', $p) : '—',
            '{رابط_إكمال_البيانات}' => $r->profileUrl(),
            '{رابط_الاختبار_القبلي}' => route('program-test', [$r->ensureTestToken(), 'pre']),
            '{رابط_الاختبار_البعدي}' => route('program-test', [$r->ensureTestToken(), 'post']),
            '{أيام_الحضور}' => (string) $att['attended'],
            '{أيام_الدورة}' => (string) $att['days'],
            '{أيام_الغياب}' => (string) $att['missed'],
            '{الغياب_المسموح}' => (string) $att['allowed'],
            '{ملاحظة_الحضور}' => match ($att['cert']) {
                'over' => 'عدد أيام الغياب: '.$att['missed'].' من '.$att['total'].'، وهذا تجاوز الحد المسموح ('.$att['max_pct'].'% من أيام الدورة)، لذلك لا تستحق الشهادة حسب سياسة المعهد. للاستفسار تواصل معنا.',
                'near' => 'عدد أيام الغياب: '.$att['missed'].'. تنبيه: الشهادة تتطلب ألا يتجاوز الغياب '.$att['max_pct'].'% من أيام الدورة'.($att['remaining'] ? '، وباقي لك يوم غياب واحد فقط.' : '، وغيابك وصل الحد، فأي غياب إضافي يُسقط الشهادة.'),
                default => $att['missed'] ? 'عدد أيام الغياب: '.$att['missed'].'. الشهادة تتطلب ألا يتجاوز الغياب '.$att['max_pct'].'% من أيام الدورة.' : 'ممتاز، استمر 👏',
            },
        ];
    }

    public static function whatsappUrl(Registration $r, string $text): string
    {
        return $r->whatsapp_link.'?text='.rawurlencode($text);
    }

    /** يرسل الرسالة بالإيميل. يرجع true عند النجاح */
    public static function email(string $type, Registration $r, Program $p): bool
    {
        if (! filled($r->email) || ! MailSettings::configured()) {
            return false;
        }
        MailSettings::apply();
        Mail::to($r->email, $r->name)->send(new TraineeMessage(self::subject($type, $p), self::render($type, $r, $p)));

        return true;
    }
}
