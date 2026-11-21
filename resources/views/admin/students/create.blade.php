@extends('layouts.admin')

@section('title', 'إضافة طالب')

@section('content')
    <form class="card" method="post" action="{{ route('admin.students.store') }}" style="max-width:720px">
        @csrf
        <div class="form-grid">
            <div class="field"><label>الاسم الكامل *</label><input type="text" name="name" value="{{ old('name') }}" required></div>
            <div class="field"><label>الجوال</label><input type="text" name="phone" value="{{ old('phone') }}" dir="ltr"></div>
            <div class="field"><label>رقم الهوية / الإقامة</label><input type="text" name="national_id" value="{{ old('national_id') }}" dir="ltr" maxlength="10"></div>
            <div class="field"><label>تاريخ الميلاد</label><input type="date" name="birth_date" value="{{ old('birth_date') }}"></div>
            <div class="field"><label>الجنس</label><select name="gender"><option value="">—</option>@foreach (\App\Models\User::GENDERS as $g)<option @selected(old('gender') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="field"><label>المؤهل</label><input type="text" name="qualification" value="{{ old('qualification') }}"></div>
            <div class="field"><label>البريد الإلكتروني (اسم الدخول) *</label><input type="email" name="email" value="{{ old('email') }}" required dir="ltr"></div>
            <div class="field"><label>كلمة المرور *</label><input type="text" name="password" required minlength="8" dir="ltr"><div class="hint">أرسلها للطالب، ويمكنه تغييرها من ملفه.</div></div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">إنشاء الحساب</button><a class="btn" href="{{ route('admin.students.index') }}">رجوع</a></div>
    </form>
@endsection
