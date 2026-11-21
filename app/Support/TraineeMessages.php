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
        'welcome' => ['الترحيب', 'أهلًا بك في {الدورة}'],
        'reminder_1d' => ['التذكير قبل الدورة بيوم', 'تذكير: {الدورة} تبدأ غدًا'],
        'reminder_am' => ['تذكير صباح الدورة', 'اليوم أول أيام {الدورة}'],
        'attendance' => ['تقرير الحضور', 'تقرير حضورك في {الدورة}'],
        'thanks' => ['الشكر بعد الدورة', 'شكرًا لإتمامك {الدورة}'],
    ];

    public const DEFAULTS = [
        'invoice_sent' => "أهلًا {الاسم} ✅\nتم تأكيد تسجيلك في دورة {الدورة} (رقم الاعتماد {رقم_الاعتماد}).\nرقم الفاتورة: {رقم_الفاتورة}\nتبدأ الدورة {التاريخ}، {الأيام} من {الوقت}.\nرابط مجموعة الدورة: {رابط_المجموعة}\nنتشرف بك في {المعهد}.",
        'welcome' => "حيّاك الله {الاسم} 🌿\nسعداء بانضمامك لدورة {الدورة} مع {المدرب}.\nأي سؤال قبل الدورة تواصل معنا على {رقم_المعهد}.",
        'reminder_1d' => "تذكير 🔔 {الاسم}\nدورتك {الدورة} تبدأ غدًا {التاريخ} الساعة {الوقت}.\nالمكان: {المكان}\nفضلًا أكّد حضورك بالرد على هذه الرسالة.",
        'reminder_am' => "صباح الخير {الاسم} ☀️\nاليوم أول أيام دورة {الدورة}، الوقت {الوقت}.\nالمكان: {المكان}\nللتواصل: {رقم_المعهد}\nنشوفك على خير.",
        'attendance' => "مرحبًا {الاسم} 📋\nتقرير حضورك في دورة {الدورة} حتى اليوم:\nحضرت {أيام_الحضور} من {أيام_الدورة} أيام.\n{ملاحظة_الحضور}",
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

    public static function vars(Registration $r, Program $p): array
    {
        $att = ProgramAttendance::summaryFor($r);

        return [
            '{الاسم}' => trim(explode(' ', trim($r->name))[0] ?: $r->name),
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
            '{أيام_الحضور}' => (string) $att['attended'],
            '{أيام_الدورة}' => (string) $att['days'],
            '{ملاحظة_الحضور}' => $att['absent'] ? 'عدد أيام الغياب: '.$att['absent'].'. الحضور شرط للحصول على الشهادة.' : 'ممتاز، استمر 👏',
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
