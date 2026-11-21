<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Review;
use App\Support\Checkout;
use App\Support\Moyasar;
use App\Support\Uploader;
use Illuminate\Http\Request;

/** الاشتراك والشراء: المجاني مباشرة، والمدفوع عبر ميسر أو التحويل البنكي، مع كوبونات الخصم */
class EnrollController extends Controller
{
    public function enroll(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);
        $user = $request->user();
        $existing = $user->enrollmentFor($course);

        if ($existing?->status === 'active') {
            return redirect()->route('learn.start', $course);
        }
        if (! $course->is_free) {
            return redirect()->route('courses.checkout', $course);
        }

        Enrollment::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => 'active', 'amount' => 0, 'payment_method' => 'free', 'approved_at' => now()]
        );

        return redirect()->route('learn.start', $course)->with('status', 'تم اشتراكك في الدورة، بالتوفيق!');
    }

    public function checkout(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);
        $user = $request->user();
        $enrollment = $user->enrollmentFor($course);
        if ($enrollment?->status === 'active') {
            return redirect()->route('learn.start', $course);
        }
        if ($course->is_free) {
            return redirect()->route('courses.show', $course);
        }

        $quote = Checkout::quote($course, $user, session('coupon.'.$course->id));
        if (! $quote['ok']) {
            session()->forget('coupon.'.$course->id);
            $quote = Checkout::quote($course, $user, null);
        }

        $moyasarOrder = Moyasar::enabled() && $quote['total'] > 0 ? Checkout::pendingMoyasarOrder($course, $user, $quote) : null;

        return view('site.courses.checkout', [
            'course' => $course,
            'enrollment' => $enrollment,
            'quote' => $quote,
            'moyasarOrder' => $moyasarOrder,
            'bankEnabled' => setting('bank_transfer_enabled', '1') === '1',
        ]);
    }

    public function applyCoupon(Request $request, Course $course)
    {
        if ($request->boolean('remove')) {
            session()->forget('coupon.'.$course->id);

            return redirect()->route('courses.checkout', $course);
        }

        $code = strtoupper(trim((string) $request->input('code')));
        $quote = Checkout::quote($course, $request->user(), $code);
        if (! $quote['ok'] || ! $quote['coupon']) {
            return redirect()->route('courses.checkout', $course)->withErrors(['code' => $quote['message'] ?? 'أدخل كود الخصم.'])->withInput();
        }
        session(['coupon.'.$course->id => $code]);

        return redirect()->route('courses.checkout', $course)->with('status', $quote['message']);
    }

    /** الكوبون غطّى كامل السعر */
    public function claimFree(Request $request, Course $course)
    {
        $user = $request->user();
        $quote = Checkout::quote($course, $user, session('coupon.'.$course->id));
        abort_unless($quote['ok'] && $quote['coupon'] && $quote['total'] <= 0, 403);

        $order = Checkout::createOrder($course, $user, $quote, 'coupon');
        $order->markPaid();
        session()->forget('coupon.'.$course->id);

        return redirect()->route('learn.start', $course)->with('status', 'تم تفعيل الدورة بالكوبون 🎉');
    }

    /** التحويل البنكي: رفع الإيصال ← طلب معلّق حتى يراجعه المحاسب */
    public function submitPayment(Request $request, Course $course)
    {
        abort_unless($course->is_published && ! $course->is_free, 404);
        abort_unless(setting('bank_transfer_enabled', '1') === '1', 403);
        $user = $request->user();
        $existing = $user->enrollmentFor($course);
        if ($existing?->status === 'active') {
            return redirect()->route('learn.start', $course);
        }

        $data = $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'note' => 'nullable|string|max:1000',
        ], [], ['receipt' => 'إيصال التحويل', 'note' => 'الملاحظات']);

        $quote = Checkout::quote($course, $user, session('coupon.'.$course->id));
        if (! $quote['ok']) {
            $quote = Checkout::quote($course, $user, null);
        }
        $order = Checkout::createOrder($course, $user, $quote, 'bank_transfer');

        Uploader::delete($existing?->receipt);
        Enrollment::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'status' => 'pending',
                'order_id' => $order->id,
                'amount' => $quote['total'],
                'payment_method' => 'bank_transfer',
                'receipt' => Uploader::storePrivate($request->file('receipt'), 'receipts'),
                'note' => $data['note'] ?? null,
            ]
        );
        session()->forget('coupon.'.$course->id);

        return redirect()->route('dashboard')->with('status', 'تم استلام إيصال التحويل ✅ (طلب رقم '.$order->number.') سيتم تفعيل الدورة بعد مراجعة الدفع.');
    }

    /** العودة من بوابة ميسر بعد الدفع */
    public function moyasarCallback(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id && $order->method === 'moyasar', 404);

        $result = Moyasar::verifyAndSettle($order, (string) $request->query('id'));
        if ($result['ok']) {
            session()->forget('coupon.'.$order->course_id);

            return redirect()->route('learn.start', $order->course)->with('status', $result['message']);
        }

        return redirect()->route('courses.checkout', $order->course)->withErrors(['payment' => $result['message']]);
    }

    public function review(Request $request, Course $course)
    {
        abort_unless($request->user()->isEnrolled($course), 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ], [], ['rating' => 'التقييم', 'comment' => 'التعليق']);

        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            $data + ['is_approved' => setting('reviews_need_approval') !== '1']
        );

        return redirect()->to(route('courses.show', $course).'#reviews')->with('status', setting('reviews_need_approval') === '1'
            ? 'شكرًا لتقييمك! سيظهر بعد المراجعة.' : 'شكرًا لتقييمك!');
    }
}
