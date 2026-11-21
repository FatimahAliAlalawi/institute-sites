<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Order;
use App\Support\Moyasar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الطلبات والمدفوعات */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $this->filtered($request)->with(['user', 'course'])->latest('id')->paginate(30)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'totals' => [
                'paid_sum' => (float) Order::where('status', 'paid')->sum('total'),
                'paid_month' => (float) Order::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
                'paid_count' => Order::where('status', 'paid')->count(),
                'pending_count' => Order::where('status', 'pending')->where('method', 'bank_transfer')->count(),
                'discounts' => (float) Order::where('status', 'paid')->sum('discount'),
            ],
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', [
            'order' => $order->load(['user', 'course', 'coupon']),
            'enrollment' => Enrollment::where('order_id', $order->id)->first(),
        ]);
    }

    /** تغيير الحالة يدويًا (اعتماد دفع، إلغاء، استرداد) أو إعادة التحقق من ميسر */
    public function update(Request $request, Order $order)
    {
        if ($request->boolean('reverify') && $order->method === 'moyasar' && $order->gateway_id) {
            $result = Moyasar::verifyAndSettle($order, $order->gateway_id);

            return back()->with('status', $result['message']);
        }

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Order::STATUSES))]]);
        if ($data['status'] === 'paid') {
            $order->markPaid();
        } else {
            $order->update(['status' => $data['status']]);
            if (in_array($data['status'], ['cancelled', 'refunded'], true)) {
                Enrollment::where('order_id', $order->id)->update(['status' => 'cancelled']);
            }
        }

        return back()->with('status', 'تم تحديث الطلب '.$order->number.': '.Order::STATUSES[$data['status']]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->with(['user', 'course'])->latest('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم الطلب', 'التاريخ', 'الطالب', 'رقم الهوية', 'الجوال', 'الدورة', 'السعر', 'الخصم', 'الكوبون', 'الإجمالي', 'طريقة الدفع', 'الحالة', 'تاريخ الدفع', 'رقم العملية']);
            foreach ($rows as $o) {
                fputcsv($out, [
                    $o->number, $o->created_at->format('Y-m-d H:i'), $o->user?->name, $o->user?->national_id, $o->user?->phone, $o->item_title,
                    $o->subtotal, $o->discount, $o->coupon_code, $o->total, $o->method_label, $o->status_label, $o->paid_at?->format('Y-m-d H:i'), $o->gateway_id,
                ]);
            }
            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function filtered(Request $request)
    {
        $q = trim((string) $request->input('q'));

        return Order::query()
            ->when($request->filled('status'), fn ($w) => $w->where('status', $request->input('status')))
            ->when($request->filled('method'), fn ($w) => $w->where('method', $request->input('method')))
            ->when($request->filled('from'), fn ($w) => $w->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($w) => $w->whereDate('created_at', '<=', $request->input('to')))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('number', 'like', "%$q%")->orWhere('coupon_code', 'like', "%$q%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('national_id', 'like', "%$q%"))));
    }
}
