@extends('layouts.admin')

@section('title', 'طلب '.$order->number)

@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn">رجوع للطلبات</a>
@endsection

@section('content')
    <div class="cols">
        <div class="card">
            <dl class="info">
                <dt>رقم الطلب</dt><dd dir="ltr" style="text-align:right">{{ $order->number }}</dd>
                <dt>الحالة</dt><dd>{{ $order->status_label }} @if ($order->paid_at)<span class="sub">— {{ $order->paid_at->format('Y-m-d H:i') }}</span>@endif</dd>
                <dt>الطالب</dt><dd>@if ($order->user)<a href="{{ route('admin.students.show', $order->user) }}">{{ $order->user->name }}</a> — {{ $order->user->national_id }}@endif</dd>
                <dt>الدورة</dt><dd>{{ $order->item_title }}</dd>
                <dt>السعر</dt><dd>{{ number_format((float) $order->subtotal, 2) }} ر.س</dd>
                <dt>الخصم</dt><dd>{{ number_format((float) $order->discount, 2) }} ر.س {{ $order->coupon_code ? '(كوبون '.$order->coupon_code.')' : '' }}</dd>
                <dt>الإجمالي</dt><dd style="font-size:20px">{{ number_format((float) $order->total, 2) }} ر.س</dd>
                <dt>طريقة الدفع</dt><dd>{{ $order->method_label }}</dd>
                @if ($order->gateway_id)<dt>رقم العملية (ميسر)</dt><dd dir="ltr" style="text-align:right">{{ $order->gateway_id }}</dd>@endif
                @if ($order->gateway_payload)
                    <dt>تفاصيل البوابة</dt>
                    <dd style="font-weight:400">
                        {{ $order->gateway_payload['status'] ?? '' }}
                        {{ isset($order->gateway_payload['company']) ? '· '.strtoupper($order->gateway_payload['company']) : '' }}
                        {{ isset($order->gateway_payload['last4']) ? '· **** '.$order->gateway_payload['last4'] : '' }}
                        {{ isset($order->gateway_payload['message']) ? '· '.$order->gateway_payload['message'] : '' }}
                    </dd>
                @endif
                <dt>الاشتراك</dt><dd>{{ $enrollment ? $enrollment->status_label : '—' }}</dd>
                @if ($enrollment?->receipt)<dt>إيصال التحويل</dt><dd><a href="{{ route('admin.enrollments.receipt', $enrollment) }}" target="_blank">عرض الإيصال</a></dd>@endif
            </dl>
        </div>
        <div>
            <form class="card" method="post" action="{{ route('admin.orders.update', $order) }}" data-confirm="تأكيد تغيير حالة الطلب؟">
                @csrf @method('PUT')
                <h2>تغيير الحالة</h2>
                <select name="status">
                    @foreach (\App\Models\Order::STATUSES as $k => $l)<option value="{{ $k }}" @selected($order->status === $k)>{{ $l }}</option>@endforeach
                </select>
                <p class="sub">"مدفوع" يفعّل الدورة للطالب. "ملغي" أو "مسترد" يلغي اشتراكه.</p>
                <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ</button></div>
            </form>
            @if ($order->method === 'moyasar' && $order->gateway_id && $order->status !== 'paid')
                <form class="card" method="post" action="{{ route('admin.orders.update', $order) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="reverify" value="1">
                    <p class="sub" style="margin-top:0">إن خُصم المبلغ من الطالب ولم تتفعّل الدورة:</p>
                    <button class="btn" type="submit"><i class="fa-solid fa-rotate"></i> إعادة التحقق من ميسر</button>
                </form>
            @endif
        </div>
    </div>
@endsection
