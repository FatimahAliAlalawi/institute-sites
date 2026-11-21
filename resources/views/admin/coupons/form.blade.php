@extends('layouts.admin')

@section('title', $coupon->exists ? 'تعديل كوبون '.$coupon->code : 'كوبون جديد')

@section('actions')
    <a href="{{ route('admin.coupons.index') }}" class="btn">رجوع</a>
@endsection

@section('content')
    @php $selectedCourses = array_map('intval', (array) old('course_ids', $coupon->course_ids ?? [])); @endphp
    <form class="card" method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
        @csrf
        @if ($coupon->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field"><label>الكود *</label><input type="text" name="code" value="{{ old('code', $coupon->code) }}" dir="ltr" required style="text-transform:uppercase"><div class="hint">مثال: RAMADAN25 — بالإنجليزي بدون مسافات.</div></div>
            <div class="field"><label>وصف داخلي</label><input type="text" name="description" value="{{ old('description', $coupon->description) }}" placeholder="مثال: حملة سناب شات"></div>
            <div class="field">
                <label>نوع الخصم</label>
                <select name="type">@foreach (\App\Models\Coupon::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('type', $coupon->type) === $k)>{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field"><label>قيمة الخصم *</label><input type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon->value) }}" required></div>
            <div class="field"><label>أقصى خصم (ر.س) — للنسبة</label><input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" placeholder="بدون حد"></div>
            <div class="field"><label>أقل مبلغ للطلب (ر.س)</label><input type="number" step="0.01" min="0" name="min_amount" value="{{ old('min_amount', $coupon->min_amount) }}" placeholder="بدون حد"></div>
            <div class="field"><label>عدد مرات الاستخدام الكلي</label><input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="غير محدود"></div>
            <div class="field"><label>مرات الاستخدام لكل طالب</label><input type="number" min="1" name="per_user_limit" value="{{ old('per_user_limit', $coupon->per_user_limit) }}" placeholder="غير محدود"></div>
            <div class="field"><label>يبدأ من</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label>ينتهي في</label><input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="field full">
                <label>يطبّق على</label>
                <div class="hint" style="margin-bottom:6px">اترك الكل بدون تحديد = كل الدورات المدفوعة.</div>
                <div class="checks">
                    @forelse ($courses as $c)
                        <label style="font-weight:500"><input type="checkbox" name="course_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $selectedCourses, true))> {{ $c->title }}</label>
                    @empty
                        <span class="sub">لا توجد دورات مدفوعة بعد.</span>
                    @endforelse
                </div>
            </div>
            <div class="field full checks"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active))> الكوبون فعّال</label></div>
        </div>
        @if ($coupon->exists)<p class="sub">استُخدم {{ $coupon->used_count }} مرة.</p>@endif
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ الكوبون</button></div>
    </form>
@endsection
