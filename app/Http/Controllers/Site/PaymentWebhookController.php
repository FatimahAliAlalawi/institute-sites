<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Moyasar;
use Illuminate\Http\Request;

/**
 * إشعارات ميسر (Webhook): تضمن تفعيل الدورة حتى لو أغلق الطالب الصفحة قبل العودة للموقع.
 * لا نثق بمحتوى الإشعار: نتحقق من الرمز السري ثم نعيد جلب الدفعة من خادم ميسر.
 */
class PaymentWebhookController extends Controller
{
    public function moyasar(Request $request)
    {
        $secret = (string) setting('moyasar_webhook_secret');
        if ($secret === '' || ! hash_equals($secret, (string) $request->input('secret_token'))) {
            return response()->json(['ok' => false], 401);
        }

        $paymentId = (string) $request->input('data.id');
        $order = Order::where('method', 'moyasar')->find((int) $request->input('data.metadata.order_id'));
        if ($order && $paymentId !== '') {
            Moyasar::verifyAndSettle($order, $paymentId);
        }

        return response()->json(['ok' => true]);
    }
}
