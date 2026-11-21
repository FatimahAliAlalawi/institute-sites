@extends('layouts.admin')

@section('title', 'إضافة مهتم لقائمة المتابعة')

@section('actions')
    <a href="{{ route('admin.registrations.index') }}" class="btn">رجوع للقائمة</a>
@endsection

@section('content')
    <form class="card" method="post" action="{{ route('admin.registrations.store') }}">
        @csrf
        <p class="sub" style="margin-top:0">لكل من سأل عبر واتساب أو اتصال أو زيارة. أضفه <b>قبل</b> ما ترد عليه، عشان ما يضيع.</p>
        <div class="form-grid">
            <div class="field">
                <label for="name">الاسم *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="field">
                <label for="phone">الجوال *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" placeholder="05XXXXXXXX" required>
                <div class="hint">يتحول تلقائيًا لصيغة 05XXXXXXXX.</div>
            </div>
            <div class="field">
                <label for="program_id">الدورة</label>
                <select id="program_id" name="program_id">
                    <option value="">لم يحدد بعد (تسجيل عام)</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}" @selected((int) old('program_id', $selected) === $p->id)>{{ $p->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="source">المصدر *</label>
                <select id="source" name="source" required>
                    @foreach (\App\Models\Registration::SOURCES as $src)
                        <option @selected(old('source', 'واتساب') === $src)>{{ $src }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">الحالة *</label>
                <select id="status" name="status">
                    @foreach (\App\Models\Registration::OPEN_STATUSES as $key)
                        <option value="{{ $key }}" @selected(old('status', 'new') === $key)>{{ \App\Models\Registration::STATUSES[$key] }}</option>
                    @endforeach
                </select>
                <div class="hint">لو رديت عليه وأرسلت العرض، اختر «تم التواصل»: تنضبط المتابعة بعد يومين تلقائيًا.</div>
            </div>
            <div class="field">
                <label for="follow_up_at">موعد المتابعة</label>
                <input type="date" id="follow_up_at" name="follow_up_at" value="{{ old('follow_up_at') }}">
                <div class="hint">اتركه فارغًا ليُحسب تلقائيًا.</div>
            </div>
            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" dir="ltr">
            </div>
            <div class="field full">
                <label for="admin_notes">ملاحظات</label>
                <textarea id="admin_notes" name="admin_notes" placeholder="مثال: يفضّل التواصل مساءً، يسأل عن دورة السلامة المهنية">{{ old('admin_notes') }}</textarea>
            </div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> حفظ وإضافة</button></div>
    </form>
@endsection
