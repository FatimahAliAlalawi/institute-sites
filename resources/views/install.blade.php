<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>تثبيت موقع معهد البصائر</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v=1">
</head>
<body>
<div class="auth">
    <form class="box wide" method="post" action="{{ route('install.store') }}">
        @csrf
        <img src="{{ asset('assets/img/logo.png') }}" alt="">
        <h1>تثبيت موقع معهد البصائر</h1>

        @php($allOk = ! in_array(false, $checks, true))
        <h3 style="font-size:16px">1) فحص الاستضافة</h3>
        <ul class="req-list">
            @foreach ($checks as $label => $ok)
                <li class="{{ $ok ? 'ok' : 'no' }}">{{ $ok ? '✔' : '✘' }} {{ $label }}</li>
            @endforeach
        </ul>
        @unless ($allOk)
            <div class="alert alert-error">بعض المتطلبات غير متوفرة. عدّل إصدار PHP أو صلاحيات المجلدات (755) من cPanel ثم حدّث الصفحة.</div>
        @endunless

        @if ($errors->any())
            <div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <h3 style="font-size:16px">2) قاعدة البيانات</h3>
        <div class="form-grid">
            <div class="field full">
                <label>رابط الموقع</label>
                <input type="url" name="app_url" value="{{ old('app_url', $detectedUrl) }}" dir="ltr" required>
            </div>
            <div class="field">
                <label>خادم قاعدة البيانات</label>
                <input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" dir="ltr" required>
            </div>
            <div class="field">
                <label>المنفذ</label>
                <input type="number" name="db_port" value="{{ old('db_port', 3306) }}" dir="ltr" required>
            </div>
            <div class="field">
                <label>اسم قاعدة البيانات</label>
                <input type="text" name="db_database" value="{{ old('db_database') }}" dir="ltr" required>
            </div>
            <div class="field">
                <label>مستخدم قاعدة البيانات</label>
                <input type="text" name="db_username" value="{{ old('db_username') }}" dir="ltr" required>
            </div>
            <div class="field full">
                <label>كلمة مرور قاعدة البيانات</label>
                <input type="password" name="db_password" dir="ltr">
            </div>
        </div>

        <h3 style="font-size:16px;margin-top:20px">3) حساب مدير لوحة التحكم</h3>
        <div class="form-grid">
            <div class="field">
                <label>الاسم</label>
                <input type="text" name="admin_name" value="{{ old('admin_name', 'مدير الموقع') }}" required>
            </div>
            <div class="field">
                <label>اسم المستخدم</label>
                <input type="text" name="admin_username" value="{{ old('admin_username', 'admin') }}" dir="ltr" required>
            </div>
            <div class="field">
                <label>كلمة المرور (8 أحرف على الأقل)</label>
                <input type="password" name="admin_password" dir="ltr" required minlength="8">
            </div>
            <div class="field">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="admin_password_confirmation" dir="ltr" required minlength="8">
            </div>
            <div class="field full checks">
                <input type="hidden" name="with_demo" value="0">
                <label><input type="checkbox" name="with_demo" value="1" checked> إضافة المحتوى الافتراضي (برامج المعهد، الشركاء، الأسئلة، نصوص الصفحات)</label>
            </div>
        </div>

        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:20px">تثبيت الموقع</button>
    </form>
</div>
</body>
</html>
