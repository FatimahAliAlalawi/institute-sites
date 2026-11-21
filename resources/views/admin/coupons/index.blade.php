@extends('layouts.admin')

@section('title', 'كوبونات الخصم')

@section('actions')
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> كوبون جديد</a>
@endsection

@section('content')
    <div class="card">
        @if ($coupons->isEmpty())
            <div class="empty">لا توجد كوبونات بعد. أنشئ كوبونًا لحملة تسويقية أو للشركاء.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الكود</th><th>الخصم</th><th>الاستخدام</th><th>الصلاحية</th><th>المبيعات</th><th>الحالة</th><th></th></tr>
                    @foreach ($coupons as $c)
                        @php $expired = $c->expires_at && $c->expires_at->isPast(); @endphp
                        <tr>
                            <td><b dir="ltr">{{ $c->code }}</b><div class="sub">{{ $c->description }}</div></td>
                            <td>{{ $c->value_label }}@if ($c->max_discount)<div class="sub">حد أقصى {{ number_format((float) $c->max_discount, 0) }} ر.س</div>@endif</td>
                            <td>{{ $c->used_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</td>
                            <td class="sub">{{ $c->starts_at?->format('Y-m-d') ?? 'الآن' }} ← {{ $c->expires_at?->format('Y-m-d') ?? 'بدون انتهاء' }}<div>{{ $c->course_ids ? count($c->course_ids).' دورة محددة' : 'كل الدورات' }}</div></td>
                            <td>{{ number_format((float) $c->revenue, 2) }} ر.س<div class="sub">خصومات {{ number_format((float) $c->discounts, 2) }}</div></td>
                            <td><span class="badge {{ $c->is_active && ! $expired ? 'b-on' : 'b-off' }}">{{ $expired ? 'منتهي' : ($c->is_active ? 'فعّال' : 'موقوف') }}</span></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.coupons.edit', $c) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.coupons.destroy', $c), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
