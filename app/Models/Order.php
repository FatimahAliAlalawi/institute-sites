<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** طلب شراء دورة (دفع إلكتروني أو تحويل بنكي أو كوبون مجاني) */
class Order extends Model
{
    public const STATUSES = [
        'pending' => 'بانتظار الدفع',
        'paid' => 'مدفوع',
        'failed' => 'فشل الدفع',
        'cancelled' => 'ملغي',
        'refunded' => 'مسترد',
    ];

    public const METHODS = [
        'moyasar' => 'دفع إلكتروني (ميسر)',
        'bank_transfer' => 'تحويل بنكي',
        'coupon' => 'مجاني بكوبون',
        'manual' => 'يدوي',
    ];

    protected $fillable = ['number', 'user_id', 'course_id', 'item_title', 'subtotal', 'discount', 'total', 'coupon_id', 'coupon_code', 'method', 'status', 'gateway_id', 'gateway_payload', 'paid_at'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'gateway_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // رقم مؤقت فريد، ثم الرقم النهائي المبني على المعرّف بعد الحفظ
        static::creating(fn (self $order) => $order->number ??= 'TMP-'.\Illuminate\Support\Str::uuid());
        static::created(function (self $order) {
            if (str_starts_with((string) $order->number, 'TMP-')) {
                $order->forceFill(['number' => 'ORD-'.now()->format('y').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    /** اعتماد الدفع: الطلب مدفوع + تفعيل الاشتراك + احتساب استخدام الكوبون (مرة واحدة فقط) */
    public function markPaid(array $payload = []): void
    {
        DB::transaction(function () use ($payload) {
            $fresh = static::whereKey($this->id)->lockForUpdate()->first();
            if ($fresh->status === 'paid') {
                return;
            }
            $fresh->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_payload' => $payload ?: $fresh->gateway_payload,
            ])->save();

            if ($fresh->coupon_id) {
                Coupon::whereKey($fresh->coupon_id)->increment('used_count');
            }

            if ($fresh->course_id) {
                Enrollment::updateOrCreate(
                    ['user_id' => $fresh->user_id, 'course_id' => $fresh->course_id],
                    ['status' => 'active', 'order_id' => $fresh->id, 'amount' => $fresh->total, 'payment_method' => $fresh->method, 'approved_at' => now()]
                );
            }
            $this->setRawAttributes($fresh->getAttributes(), true);
        });
    }
}
