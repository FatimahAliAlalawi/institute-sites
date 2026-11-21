<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * بوابة ميسر (مدى، فيزا/ماستركارد، Apple Pay، STC Pay).
 * لا نثق بنتيجة المتصفح أبدًا: كل دفعة يُتحقق منها من خادم ميسر (الحالة + المبلغ + العملة + رقم الطلب).
 */
class Moyasar
{
    public const JS_VERSION = '1.14.0';

    public static function enabled(): bool
    {
        return setting('moyasar_enabled') === '1' && setting('moyasar_publishable_key') && setting('moyasar_secret_key');
    }

    public static function methods(): array
    {
        $methods = ['creditcard'];
        if (setting('moyasar_applepay') === '1') {
            $methods[] = 'applepay';
        }
        if (setting('moyasar_stcpay') === '1') {
            $methods[] = 'stcpay';
        }

        return $methods;
    }

    public static function apiBase(): string
    {
        return rtrim((string) setting('moyasar_api_base', 'https://api.moyasar.com/v1'), '/');
    }

    /** جلب الدفعة من خادم ميسر بالمفتاح السري */
    public static function fetchPayment(string $paymentId): ?array
    {
        if (! preg_match('/^[A-Za-z0-9_\-]{6,100}$/', $paymentId)) {
            return null;
        }
        try {
            $response = Http::withBasicAuth((string) setting('moyasar_secret_key'), '')
                ->acceptJson()->timeout(20)
                ->get(static::apiBase().'/payments/'.$paymentId);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * يتحقق من الدفعة ويطابقها مع الطلب ثم يعتمدها.
     *
     * @return array{ok:bool,message:string}
     */
    public static function verifyAndSettle(Order $order, string $paymentId): array
    {
        if ($order->status === 'paid') {
            return ['ok' => true, 'message' => 'تم الدفع مسبقًا.'];
        }

        $payment = static::fetchPayment($paymentId);
        if (! $payment) {
            return ['ok' => false, 'message' => 'تعذّر التحقق من الدفع مع البوابة. إن خُصم المبلغ تواصل معنا برقم الطلب '.$order->number.'.'];
        }

        $expected = (int) round((float) $order->total * 100);
        $orderRef = (string) ($payment['metadata']['order_id'] ?? '');
        $checks = [
            'status' => ($payment['status'] ?? '') === 'paid',
            'amount' => (int) ($payment['amount'] ?? -1) === $expected,
            'currency' => strtoupper((string) ($payment['currency'] ?? '')) === 'SAR',
            'order' => $orderRef === (string) $order->id,
        ];

        $order->forceFill(['gateway_id' => $paymentId, 'gateway_payload' => static::summary($payment)])->save();

        if (! $checks['status']) {
            $order->forceFill(['status' => 'failed'])->save();

            return ['ok' => false, 'message' => 'لم تتم عملية الدفع: '.($payment['source']['message'] ?? 'تم رفض العملية').'.'];
        }
        if (! $checks['amount'] || ! $checks['currency'] || ! $checks['order']) {
            report(new \RuntimeException('Moyasar mismatch for order '.$order->number.': '.json_encode($checks)));

            return ['ok' => false, 'message' => 'بيانات الدفع لا تطابق الطلب '.$order->number.' — لم يتم التفعيل، وسيراجع فريقنا العملية.'];
        }

        $order->markPaid(static::summary($payment));

        return ['ok' => true, 'message' => 'تم الدفع بنجاح ✅ وتفعيل الدورة.'];
    }

    /** نحتفظ بملخص آمن فقط (بدون بيانات البطاقة الحساسة) */
    protected static function summary(array $payment): array
    {
        return [
            'id' => $payment['id'] ?? null,
            'status' => $payment['status'] ?? null,
            'amount' => $payment['amount'] ?? null,
            'currency' => $payment['currency'] ?? null,
            'source_type' => $payment['source']['type'] ?? null,
            'company' => $payment['source']['company'] ?? null,
            'last4' => isset($payment['source']['number']) ? substr(preg_replace('/\D/', '', (string) $payment['source']['number']), -4) : null,
            'message' => $payment['source']['message'] ?? null,
            'created_at' => $payment['created_at'] ?? null,
        ];
    }
}
