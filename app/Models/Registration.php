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

    public const SOURCES = ['الموقع', 'واتساب', 'إعلان ممول', 'إنستغرام', 'سناب شات', 'إكس', 'صديق أو معرفة', 'اتصال هاتفي', 'زيارة للمعهد', 'متدرب سابق', 'أخرى'];

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
