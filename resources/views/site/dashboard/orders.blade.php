@extends('site.dashboard.layout')

@section('title', 'مدفوعاتي')

@section('dash')
    @if ($orders->isEmpty())
        <div class="empty">لا توجد مدفوعات بعد.</div>
    @else
        <table class="table">
            <tr><th>رقم الطلب</th><th>الدورة</th><th>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>التاريخ</th></tr>
            @foreach ($orders as $o)
                <tr>
                    <td dir="ltr" style="text-align:right">{{ $o->number }}</td>
                    <td>{{ $o->item_title }}</td>
                    <td>{{ number_format((float) $o->total, 2) }} ر.س @if ((float) $o->discount > 0)<div class="muted" style="font-size:12px">خصم {{ number_format((float) $o->discount, 2) }}</div>@endif</td>
                    <td>{{ $o->method_label }}</td>
                    <td><span class="pill {{ ['paid' => 'active', 'pending' => 'pending'][$o->status] ?? 'cancelled' }}">{{ $o->status_label }}</span></td>
                    <td>{{ $o->created_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </table>
        {{ $orders->links() }}
    @endif
@endsection
