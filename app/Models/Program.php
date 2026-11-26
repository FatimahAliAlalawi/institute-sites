<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasSlug;

    public const CATEGORIES = [
        'youth' => 'برامج الشباب',
        'community' => 'برامج المجتمع',
        'skills' => 'دورات الأفراد',
        'corporate' => 'برامج الشركات',
    ];

    public const MODES = ['حضوري', 'عن بُعد', 'مدمج'];

    /** حالات الاعتماد (للخطة التدريبية وللإعلان) */
    public const APPROVAL_STATUSES = [
        'none' => 'غير مُدرج',
        'pending' => 'بانتظار اعتماد الإدارة',
        'approved' => 'معتمد من الإدارة',
        'rejected' => 'مرفوض — يحتاج تعديل',
    ];

    /**
     * خطوات «آلية التسجيل» لكل دورة: [العنوان، التفاصيل، النوع، التوقيت]
     * النوع trainee = تُنفّذ لكل متدرب، course = مرة واحدة للدورة.
     * التوقيت: null = عند التسجيل، وإلا عدد الأيام بالنسبة لتاريخ البدء (−3 = قبلها بثلاثة أيام).
     */
    public const OPS_STEPS = [
        'register' => ['تسجيل المتدرب', 'استمارة إلكترونية أو حضور مباشر', 'trainee', null],
        'invoice' => ['إنشاء الفاتورة', 'مباشرة بعد التسجيل', 'trainee', null],
        'invoice_sent' => ['إرسال الفاتورة + رابط المجموعة', 'في رسالة واحدة للمتدرب', 'trainee', null],
        'profile' => ['إكمال بيانات المتدرب', 'رابط يكمل فيه الاسم الرباعي والهوية والميلاد (يُرسل تلقائيًا بالإيميل بعد الدفع)', 'trainee', null],
        'welcome' => ['رسالة ترحيبية', 'ترحيب بالمتدرب هاتفيًا', 'trainee', null],
        'prep_sheet' => ['إنشاء جدول التحضير', 'ومشاركته مع المدربين', 'course', -3],
        'room' => ['تجهيز القاعة والملازم', 'تجهيز المكان والمواد', 'course', -3],
        'reminder_1d' => ['رسالة تذكير', 'تذكير المتدربين قبل الدورة بيوم', 'trainee', -1],
        'reminder_am' => ['تذكير صباح الدورة', 'تذكير المتدربين صباح يوم الدورة', 'trainee', 0],
        'tvtc' => ['إضافة الأسماء في المؤسسة', 'في المؤسسة العامة للتدريب التقني والمهني', 'trainee', 0],
    ];

    /** خطوات المتدرب التي تُؤشَّر يدويًا (التسجيل نفسه = حالة «مقبول») */
    public const TRAINEE_STEPS = ['invoice', 'invoice_sent', 'profile', 'welcome', 'reminder_1d', 'reminder_am', 'tvtc'];

    /** رسائل بعد بدء الدورة تُتابع في نفس الجدول */
    public const AFTER_STEPS = ['pre_test' => 'الاختبار القبلي', 'attendance' => 'تقرير الحضور', 'post_test' => 'الاختبار البعدي', 'thanks' => 'الشكر بعد الدورة'];

    /** حالة تنفيذ تُحدد يدويًا في «متابعة الخطة» (الفارغ = حسب الموعد) */
    public const PLAN_EXEC = ['postponed' => 'مؤجلة', 'cancelled' => 'ملغاة'];

    /** الحقول التي يظهر فيها الإعلان؛ تعديل أيٍّ منها يعيد الإعلان لانتظار الاعتماد */
    public const AD_FIELDS = ['title', 'accreditation_no', 'duration', 'schedule_days', 'schedule_time', 'trainer_name', 'hours', 'price', 'sale_price', 'offer_ends_at', 'start_date', 'contact_phone', 'mode', 'location'];

    /** الحقول التي تمس الخطة؛ تعديل أيٍّ منها يعيد الخطة لانتظار الاعتماد */
    public const PLAN_FIELDS = ['title', 'start_date', 'duration', 'schedule_days', 'schedule_time', 'hours', 'price', 'sale_price', 'seats', 'mode', 'location'];

    protected $fillable = [
        'title', 'slug', 'category', 'summary', 'description', 'goal', 'target_audience', 'duration', 'hours',
        'location', 'mode', 'seats', 'price', 'start_date', 'certificate', 'steps', 'outcomes', 'image',
        'external_link', 'registration_open', 'is_featured', 'is_active', 'sort',
        'accreditation_no', 'sale_price', 'offer_ends_at', 'contact_phone',
        'plan_status', 'plan_approved_by', 'plan_approved_at', 'ad_status', 'ad_approved_by', 'ad_approved_at', 'approval_note',
        'schedule_days', 'schedule_time', 'trainer_name', 'group_link', 'ops', 'trainer_admin_id', 'sessions',
        'pre_test_open', 'post_test_open', 'plan_baseline_date', 'plan_exec', 'plan_exec_note',
    ];

    /** أيام الأسبوع بترتيب الأسبوع السعودي (رقم Carbon => الاسم) */
    public const WEEKDAYS = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];

    protected $casts = [
        'start_date' => 'date',
        'offer_ends_at' => 'date',
        'ops' => 'array',
        'sessions' => 'array',
        'plan_baseline_date' => 'date',
        'pre_test_open' => 'boolean',
        'post_test_open' => 'boolean',
        'plan_approved_at' => 'datetime',
        'ad_approved_at' => 'datetime',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'registration_open' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** حساب المدرب في لوحة التحكم (يرى تحضير هذه الدورة فقط) */
    public function trainerAdmin()
    {
        return $this->belongsTo(Admin::class, 'trainer_admin_id');
    }

    /** أيام الدورة في التقويم مرتبة (نصوص Y-m-d) */
    public function getSessionDatesAttribute(): array
    {
        $dates = array_values(array_unique(array_filter((array) ($this->sessions ?? []))));
        sort($dates);

        return $dates;
    }

    /** يولّد أيام الدورة من تاريخ البدء وأيام الأسبوع وعدد الأيام */
    public static function generateSessions(\Illuminate\Support\Carbon $start, array $weekdays, int $count): array
    {
        $weekdays = array_map('intval', $weekdays);
        if (! $weekdays || $count < 1) {
            return [];
        }
        $dates = [];
        $d = $start->copy()->startOfDay();
        $guard = 0;
        while (count($dates) < min($count, 200) && $guard++ < 1000) {
            if (in_array($d->dayOfWeek, $weekdays, true)) {
                $dates[] = $d->toDateString();
            }
            $d->addDay();
        }

        return $dates;
    }

    /** وقت الجلسة من نص «وقت الدورة» مثل «5:00 م — 8:00 م» => [[17,0],[20,0]] أو null */
    public function getSessionTimesAttribute(): ?array
    {
        $text = strtr((string) $this->schedule_time, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => ':']);
        if (! preg_match_all('/(\d{1,2})(?::(\d{2}))?\s*(ص|م|am|pm|AM|PM)?/u', $text, $m, PREG_SET_ORDER) || count($m) < 2) {
            return null;
        }
        $out = [];
        foreach (array_slice($m, 0, 2) as $i => $p) {
            $h = (int) $p[1];
            $min = (int) ($p[2] ?? 0);
            $suffix = ($p[3] ?? '') ?: ($m[1][3] ?? '');
            if (in_array($suffix, ['م', 'pm', 'PM'], true) && $h < 12) {
                $h += 12;
            }
            if (in_array($suffix, ['ص', 'am', 'AM'], true) && $h === 12) {
                $h = 0;
            }
            if ($h > 23 || $min > 59) {
                return null;
            }
            $out[] = [$h, $min];
        }

        return $out;
    }

    /** أسئلة الاختبار القبلي والبعدي */
    public function reports()
    {
        return $this->hasMany(ProgramReport::class)->latest('id');
    }

    /** أيام الدورة مقسّمة على الأسابيع (الأحد => أيام ذلك الأسبوع) */
    public function sessionWeeks(): array
    {
        $dates = $this->session_dates ?: ($this->start_date ? [$this->start_date->toDateString()] : []);
        $weeks = [];
        foreach ($dates as $d) {
            $weeks[\Illuminate\Support\Carbon::parse($d)->startOfWeek(\Illuminate\Support\Carbon::SUNDAY)->toDateString()][] = $d;
        }

        return $weeks;
    }

    public function questions()
    {
        return $this->hasMany(ProgramQuestion::class)->orderBy('sort')->orderBy('id');
    }

    /** هل الاختبار (pre أو post) مفتوح للمتدربين؟ */
    public function testOpen(string $kind): bool
    {
        return (bool) $this->{$kind.'_test_open'} && $this->questions->isNotEmpty();
    }

    /** الحد الأدنى من المتدربين لعقد الدورة */
    public static function minTrainees(): int
    {
        $v = setting('plan_min_trainees');

        return is_numeric($v) ? max(1, (int) $v) : 3;
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /** طلبات تحجز مقعدًا (مقبول أو أكمل الدورة) */
    public function seatRegistrations()
    {
        return $this->hasMany(Registration::class)->whereIn('status', Registration::SEAT_STATUSES);
    }

    /** عدد المقاعد المحجوزة (يستخدم withCount إن وُجد لتجنب استعلام إضافي) */
    public function getSeatsTakenAttribute(): int
    {
        return (int) ($this->attributes['seat_registrations_count'] ?? $this->seatRegistrations()->count());
    }

    /** المقاعد المتبقية، أو null إن لم يُحدد عدد مقاعد */
    public function getSeatsLeftAttribute(): ?int
    {
        return $this->seats ? max(0, $this->seats - $this->seats_taken) : null;
    }

    public function getIsFullAttribute(): bool
    {
        return $this->seats_left === 0;
    }

    /** يغلق التسجيل تلقائيًا عند اكتمال المقاعد. يرجع true إذا أُغلق الآن */
    public function closeIfFull(): bool
    {
        if ($this->registration_open && $this->seats && $this->seatRegistrations()->count() >= $this->seats) {
            $this->forceFill(['registration_open' => false])->save();

            return true;
        }

        return false;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->latest('id');
    }

    /** البرامج المعتمدة في الخطة التدريبية */
    public function scopeInPlan(Builder $query): Builder
    {
        return $query->where('plan_status', 'approved');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getPriceLabelAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return (float) $this->price == 0 ? 'مجاني' : number_format((float) $this->price, 0).' ر.س';
    }

    /** هل العرض (الخصم) ساري اليوم؟ */
    public function getHasOfferAttribute(): bool
    {
        return $this->price !== null && $this->sale_price !== null
            && (float) $this->sale_price < (float) $this->price
            && ($this->offer_ends_at === null || ! $this->offer_ends_at->isPast() || $this->offer_ends_at->isToday());
    }

    /** السعر بعد الخصم إن كان العرض ساريًا */
    public function getOfferPriceLabelAttribute(): ?string
    {
        if (! $this->has_offer) {
            return null;
        }

        return (float) $this->sale_price == 0 ? 'مجاني' : number_format((float) $this->sale_price, 0).' ر.س';
    }

    public function getDiscountPercentAttribute(): ?int
    {
        return $this->has_offer && (float) $this->price > 0
            ? (int) round(100 - ((float) $this->sale_price / (float) $this->price) * 100)
            : null;
    }

    /** رقم التواصل في الإعلان: رقم البرنامج، وإلا واتساب المعهد، وإلا هاتفه */
    public function getContactNumberAttribute(): ?string
    {
        return $this->contact_phone ?: (setting('whatsapp') ?: setting('phone'));
    }

    /** رابط واتساب مباشر للاستفسار عن البرنامج */
    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->contact_number);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '05')) {
            $digits = '966'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode('استفسار عن: '.$this->title);
    }

    /** المسؤولون عن خطوة: من الدورة نفسها، وإلا الافتراضي من الإعدادات */
    public function opsAssignees(string $step): string
    {
        $own = $this->ops['assignees'][$step] ?? null;
        if ($own !== null && $own !== '') {
            return $own;
        }
        $defaults = json_decode((string) setting('ops_assignees', '{}'), true) ?: [];

        return (string) ($defaults[$step] ?? '');
    }

    /** موعد الخطوة (تاريخ)، أو null إن كانت عند التسجيل أو لا يوجد تاريخ بدء */
    public function opsDue(string $step): ?\Illuminate\Support\Carbon
    {
        $offset = self::OPS_STEPS[$step][3] ?? null;
        if ($offset === null || ! $this->start_date) {
            return null;
        }

        return $this->start_date->copy()->addDays($offset);
    }

    public function opsCourseDone(string $step): ?array
    {
        return $this->ops['done'][$step] ?? null;
    }

    public function getPlanStatusLabelAttribute(): string
    {
        return self::APPROVAL_STATUSES[$this->plan_status ?: 'none'] ?? $this->plan_status;
    }

    public function getAdStatusLabelAttribute(): string
    {
        return self::APPROVAL_STATUSES[$this->ad_status ?: 'none'] ?? $this->ad_status;
    }

    /** خطوات التنفيذ: كل سطر بصيغة "العنوان | الوصف" */
    public function getStepsListAttribute(): array
    {
        return array_map(function ($line) {
            [$title, $desc] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

            return ['title' => $title, 'desc' => $desc];
        }, lines($this->steps));
    }

    public function getOutcomesListAttribute(): array
    {
        return lines($this->outcomes);
    }

    public function getCanRegisterAttribute(): bool
    {
        return $this->is_active && $this->registration_open && ! $this->is_full;
    }
}
