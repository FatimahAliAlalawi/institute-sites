@extends('layouts.admin')

@section('title', 'الطلبات والمدفوعات')

@section('actions')
    <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير Excel</a>
@endsection

@section('content')
    <div class="stats">
        <div class="stat"><b>{{ number_format($totals['paid_sum'], 2) }}</b><span>إجمالي المبيعات المدفوعة (ر.س)</span></div>
        <div class="stat"><b>{{ number_format($totals['paid_month'], 2) }}</b><span>مبيعات هذا الشهر (ر.س)</span></div>
        <div class="stat"><b>{{ $totals['paid_count'] }}</b><span>طلب مدفوع</span></div>
        <div class="stat"><b>{{ $totals['pending_count'] }}</b><span>تحويل بنكي بانتظار المراجعة</span></div>
        <div class="stat"><b>{{ number_format($totals['discounts'], 2) }}</b><span>إجمالي الخصومات (ر.س)</span></div>
    </div>

    <div class="card">
        <form class="filters" method="get">
            <div class="field"><label>بحث</label><input type="search" name="q" value="{{ request('q') }}" placeholder="رقم الطلب، الطالب، الهوية، الكوبون"></div>
            <div class="field">
                <label>الحالة</label>
                <select name="status"><option value="">الكل</option>@foreach (\App\Models\Order::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field">
                <label>طريقة الدفع</label>
                <select name="method"><option value="">الكل</option>@foreach (\App\Models\Order::METHODS as $k => $l)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field"><label>من</label><input type="date" name="from" value="{{ request('from') }}"></div>
            <div class="field"><label>إلى</label><input type="date" name="to" value="{{ request('to') }}"></div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> تصفية</button>
        </form>

        @if ($orders->isEmpty())
            <div class="empty">لا توجد طلبات.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الطلب</th><th>الطالب</th><th>الدورة</th><th>الإجمالي</th><th>الطريقة</th><th>الحالة</th><th>التاريخ</th></tr>
                    @foreach ($orders as $o)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $o) }}"><b dir="ltr">{{ $o->number }}</b></a></td>
                            <td>{{ $o->user?->name }}<div class="sub" dir="ltr" style="text-align:right">{{ $o->user?->phone }}</div></td>
                            <td class="sub">{{ $o->item_title }}</td>
                            <td><b>{{ number_format((float) $o->total, 2) }}</b>@if ((float) $o->discount > 0)<div class="sub">خصم {{ number_format((float) $o->discount, 2) }} ({{ $o->coupon_code }})</div>@endif</td>
                            <td class="sub">{{ $o->method_label }}</td>
                            <td><span class="badge {{ ['paid' => 'b-accepted', 'pending' => 'b-contacted', 'failed' => 'b-rejected', 'cancelled' => 'b-off', 'refunded' => 'b-off'][$o->status] ?? '' }}">{{ $o->status_label }}</span></td>
                            <td class="sub">{{ $o->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $orders->links() }}
        @endif
    </div>
@endsection
