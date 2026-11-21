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

    /** الحقول التي يظهر فيها الإعلان؛ تعديل أيٍّ منها يعيد الإعلان لانتظار الاعتماد */
    public const AD_FIELDS = ['title', 'accreditation_no', 'duration', 'hours', 'price', 'sale_price', 'offer_ends_at', 'start_date', 'contact_phone', 'mode', 'location'];

    /** الحقول التي تمس الخطة؛ تعديل أيٍّ منها يعيد الخطة لانتظار الاعتماد */
    public const PLAN_FIELDS = ['title', 'start_date', 'duration', 'hours', 'price', 'sale_price', 'seats', 'mode', 'location'];

    protected $fillable = [
        'title', 'slug', 'category', 'summary', 'description', 'goal', 'target_audience', 'duration', 'hours',
        'location', 'mode', 'seats', 'price', 'start_date', 'certificate', 'steps', 'outcomes', 'image',
        'external_link', 'registration_open', 'is_featured', 'is_active', 'sort',
        'accreditation_no', 'sale_price', 'offer_ends_at', 'contact_phone',
        'plan_status', 'plan_approved_by', 'plan_approved_at', 'ad_status', 'ad_approved_by', 'ad_approved_at', 'approval_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'offer_ends_at' => 'date',
        'plan_approved_at' => 'datetime',
        'ad_approved_at' => 'datetime',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'registration_open' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

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
