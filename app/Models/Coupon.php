<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** كوبون خصم (نسبة أو مبلغ ثابت) */
class Coupon extends Model
{
    public const TYPES = ['percent' => 'نسبة مئوية %', 'fixed' => 'مبلغ ثابت (ر.س)'];

    protected $fillable = ['code', 'description', 'type', 'value', 'max_discount', 'min_amount', 'usage_limit', 'per_user_limit', 'used_count', 'course_ids', 'starts_at', 'expires_at', 'is_active'];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'course_ids' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /**
     * التحقق من الكوبون وحساب الخصم.
     *
     * @return array{ok:bool,discount:float,message:string}
     */
    public function evaluate(Course $course, ?User $user, float $amount): array
    {
        $fail = fn (string $message) => ['ok' => false, 'discount' => 0.0, 'message' => $message];

        if (! $this->is_active) {
            return $fail('هذا الكوبون غير مفعّل.');
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return $fail('هذا الكوبون لم يبدأ بعد.');
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return $fail('انتهت صلاحية هذا الكوبون.');
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return $fail('تم استنفاد عدد مرات استخدام هذا الكوبون.');
        }
        if ($this->course_ids && ! in_array($course->id, array_map('intval', $this->course_ids), true)) {
            return $fail('هذا الكوبون غير صالح لهذه الدورة.');
        }
        if ($this->min_amount !== null && $amount < (float) $this->min_amount) {
            return $fail('الحد الأدنى لاستخدام هذا الكوبون '.number_format((float) $this->min_amount, 0).' ر.س.');
        }
        if ($user && $this->per_user_limit) {
            $used = $this->orders()->where('user_id', $user->id)->where('status', 'paid')->count();
            if ($used >= $this->per_user_limit) {
                return $fail('سبق لك استخدام هذا الكوبون.');
            }
        }

        $discount = $this->type === 'percent' ? $amount * (float) $this->value / 100 : (float) $this->value;
        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }
        $discount = round(min($discount, $amount), 2);

        return ['ok' => true, 'discount' => $discount, 'message' => 'تم تطبيق الكوبون: خصم '.number_format($discount, 2).' ر.س'];
    }

    public function getValueLabelAttribute(): string
    {
        return $this->type === 'percent' ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'%' : number_format((float) $this->value, 2).' ر.س';
    }
}
