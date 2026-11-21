<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;

/** حساب سعر الشراء (التخفيض المجدول + الكوبون) وإنشاء الطلبات */
class Checkout
{
    /**
     * @return array{subtotal:float,discount:float,total:float,coupon:?Coupon,message:?string,ok:bool}
     */
    public static function quote(Course $course, ?User $user, ?string $code): array
    {
        $subtotal = round($course->effective_price, 2);
        $result = ['subtotal' => $subtotal, 'discount' => 0.0, 'total' => $subtotal, 'coupon' => null, 'message' => null, 'ok' => true];

        if (trim((string) $code) === '') {
            return $result;
        }
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            return ['message' => 'كود الخصم غير صحيح.', 'ok' => false] + $result;
        }
        $check = $coupon->evaluate($course, $user, $subtotal);
        if (! $check['ok']) {
            return ['message' => $check['message'], 'ok' => false] + $result;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $check['discount'],
            'total' => round(max(0, $subtotal - $check['discount']), 2),
            'coupon' => $coupon,
            'message' => $check['message'],
            'ok' => true,
        ];
    }

    public static function createOrder(Course $course, User $user, array $quote, string $method): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'item_title' => $course->title,
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'total' => $quote['total'],
            'coupon_id' => $quote['coupon']?->id,
            'coupon_code' => $quote['coupon']?->code,
            'method' => $method,
            'status' => 'pending',
        ]);
    }

    /** طلب دفع إلكتروني معلّق بنفس المبلغ والكوبون (يُعاد استخدامه بدل إنشاء طلبات مكررة) */
    public static function pendingMoyasarOrder(Course $course, User $user, array $quote): Order
    {
        $existing = Order::where('user_id', $user->id)->where('course_id', $course->id)
            ->where('method', 'moyasar')->where('status', 'pending')
            ->where('total', $quote['total'])->where('coupon_id', $quote['coupon']?->id)
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')->first();

        return $existing ?? static::createOrder($course, $user, $quote, 'moyasar');
    }
}
