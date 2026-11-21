@extends('layouts.admin')

@section('title', 'حسابي')

@section('content')
    <form class="card" method="post" action="{{ route('admin.account.update') }}" style="max-width:640px">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="field">
                <label for="name">الاسم</label>
                <input type="text" id="name" name="name" value="{{ old('name', $currentAdmin->name) }}" required>
            </div>
            <div class="field">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" value="{{ old('username', $currentAdmin->username) }}" required autocomplete="username">
            </div>
            <div class="field">
                <label for="password">كلمة المرور الجديدة</label>
                <input type="password" id="password" name="password" autocomplete="new-password">
                <div class="hint">اتركها فارغة إن لم ترد تغييرها (8 أحرف على الأقل).</div>
            </div>
            <div class="field">
                <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
            </div>
            <div class="field full">
                <label for="current_password">كلمة المرور الحالية *</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                <div class="hint">مطلوبة لتأكيد أي تغيير.</div>
            </div>
        </div>
        <div class="form-foot"><button type="submit" class="btn btn-primary">حفظ</button></div>
    </form>
@endsection
