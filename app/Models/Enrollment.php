<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** اشتراك طالب في دورة (مجاني = نشط مباشرة، مدفوع = بانتظار تأكيد التحويل) */
class Enrollment extends Model
{
    public const STATUSES = [
        'pending' => 'بانتظار تأكيد الدفع',
        'active' => 'نشط',
        'cancelled' => 'ملغي',
    ];

    public const METHODS = [
        'free' => 'مجاني',
        'bank_transfer' => 'تحويل بنكي',
        'manual' => 'إضافة يدوية',
        'imported' => 'مستورد من النظام السابق',
        'moyasar' => 'دفع إلكتروني (ميسر)',
        'coupon' => 'مجاني بكوبون',
    ];

    protected $fillable = ['user_id', 'course_id', 'order_id', 'status', 'amount', 'payment_method', 'receipt', 'note', 'approved_at', 'completed_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
