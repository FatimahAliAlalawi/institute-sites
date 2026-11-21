@extends('layouts.admin')

@section('title', 'شريط الإعلان')

@section('content')
    <form class="card" method="post" action="{{ route('admin.marketing.update') }}" style="max-width:820px">
        @csrf @method('PUT')
        <p class="sub" style="margin-top:0">شريط ملوّن أعلى كل صفحات الموقع — مثالي لإعلان عرض، كوبون خصم، أو فتح التسجيل.</p>
        <div class="form-grid">
            <div class="field full checks"><label><input type="checkbox" name="announcement_enabled" value="1" @checked(setting('announcement_enabled') === '1')> إظهار الشريط</label></div>
            <div class="field full"><label>نص الإعلان</label><input type="text" name="announcement_text" value="{{ old('announcement_text', setting('announcement_text')) }}" placeholder="🎉 خصم 20% على كل الدورات بكود RAMADAN20 حتى نهاية الشهر"></div>
            <div class="field"><label>نص الزر (اختياري)</label><input type="text" name="announcement_button" value="{{ old('announcement_button', setting('announcement_button')) }}" placeholder="سجّل الآن"></div>
            <div class="field"><label>رابط الزر</label><input type="text" name="announcement_link" value="{{ old('announcement_link', setting('announcement_link')) }}" dir="ltr" placeholder="/courses"></div>
            <div class="field">
                <label>لون الشريط</label>
                <div class="actions">
                    <input type="color" value="{{ setting('announcement_bg', '#16794a') }}" oninput="this.nextElementSibling.value=this.value">
                    <input type="text" name="announcement_bg" value="{{ old('announcement_bg', setting('announcement_bg', '#16794a')) }}" dir="ltr" style="width:120px">
                </div>
            </div>
            <div class="field"><label>يختفي تلقائيًا في (اختياري)</label><input type="datetime-local" name="announcement_ends_at" value="{{ old('announcement_ends_at', setting('announcement_ends_at') ? \Illuminate\Support\Carbon::parse(setting('announcement_ends_at'))->format('Y-m-d\TH:i') : '') }}"></div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ</button></div>
        <p class="sub">لإنشاء كود خصم: <a href="{{ route('admin.coupons.create') }}">كوبون جديد</a>.</p>
    </form>
@endsection
