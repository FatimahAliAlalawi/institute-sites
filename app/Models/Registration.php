<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** طلب تسجيل = سطر في قائمة المتابعة: من أول سؤال للمهتم إلى إكمال الدورة */
class Registration extends Model
{
    public const STATUSES = [
        'new' => 'جديد (سأل)',
        'contacted' => 'تم التواصل (أرسلنا العرض)',
        'no_reply' => 'لم يرد',
        'accepted' => 'مقبول (سجّل ودفع)',
        'completed' => 'أكمل الدورة',
        'withdrawn' => 'اعتذر / انسحب',
        'rejected' => 'غير مقبول',
    ];

    /** حالات تحتاج متابعة من خدمة العملاء */
    public const OPEN_STATUSES = ['new', 'contacted', 'no_reply'];

    /** حالات تحجز مقعدًا في الدورة */
    public const SEAT_STATUSES = ['accepted', 'completed'];

    public const SOURCES = ['الموقع', 'نموذج Google', 'ملف Excel', 'واتساب', 'إعلان ممول', 'إنستغرام', 'سناب شات', 'إكس', 'صديق أو معرفة', 'اتصال هاتفي', 'زيارة للمعهد', 'متدرب سابق', 'أخرى'];

    public const ATTENDANCE = [
        'confirmed' => 'أكّد الحضور',
        'declined' => 'اعتذر عن الحضور',
        'no_answer' => 'لم يرد على التذكير',
    ];

    public const QUALIFICATIONS = ['ثانوي أو أقل', 'دبلوم', 'بكالوريوس', 'دراسات عليا'];

    public const EMPLOYMENT = ['باحث عن عمل', 'موظف قطاع خاص', 'موظف قطاع حكومي', 'موظف قطاع غير ربحي', 'طالب'];

    protected $fillable = [
        'program_id', 'name', 'phone', 'email', 'city', 'gender', 'qualification',
        'employment_status', 'notes', 'status', 'admin_notes', 'ip', 'extra',
        'source', 'follow_up_at', 'invoice_no', 'attendance', 'coupon_code', 'steps',
    ];

    protected $casts = ['extra' => 'array', 'follow_up_at' => 'date', 'steps' => 'array'];

    /** هل نُفّذت خطوة من خطوات آلية التسجيل لهذا المتدرب؟ */
    public function stepDone(string $step): bool
    {
        if ($step === 'invoice' && filled($this->invoice_no)) {
            return true;
        }

        return ! empty($this->steps[$step]);
    }

    /** الجوال بصيغة 05XXXXXXXX إن أمكن (يقبل الأرقام العربية و 966) */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', strtr($phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
        if (str_starts_with($digits, '009665')) {
            $digits = '0'.substr($digits, 5);
        } elseif (str_starts_with($digits, '9665')) {
            $digits = '0'.substr($digits, 3);
        } elseif (str_starts_with($digits, '5') && strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return $digits ?: trim($phone);
    }

    /** رمز سري لرابط اختبار المتدرب (يُنشأ عند أول حاجة) */
    public function ensureTestToken(): string
    {
        if (! $this->test_token) {
            $this->forceFill(['test_token' => \Illuminate\Support\Str::random(40)])->save();
        }

        return $this->test_token;
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /** متابعات مستحقة اليوم أو متأخرة */
    public function scopeDueFollowUp(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES)
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now()->toDateString());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getAttendanceLabelAttribute(): ?string
    {
        return $this->attendance ? (self::ATTENDANCE[$this->attendance] ?? $this->attendance) : null;
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true)
            && $this->follow_up_at !== null && $this->follow_up_at->lt(now()->startOfDay());
    }

    public function getWhatsappLinkAttribute(): string
    {
        return 'https://wa.me/'.preg_replace('/^0/', '966', preg_replace('/\D/', '', (string) $this->phone));
    }
}
