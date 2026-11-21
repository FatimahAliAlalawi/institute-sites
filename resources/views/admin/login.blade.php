<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>تسجيل الدخول — لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v=1">
</head>
<body>
<div class="auth">
    <form class="box" method="post" action="{{ route('admin.login.attempt') }}">
        @csrf
        <img src="{{ media_url(setting('logo', 'assets/img/logo.png')) }}" alt="">
        <h1>لوحة تحكم الموقع</h1>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <div class="field">
            <label for="username">اسم المستخدم</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
        </div>
        <div class="field">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">دخول</button>
        <p style="text-align:center;margin:16px 0 0"><a href="{{ route('home') }}">← العودة للموقع</a></p>
    </form>
</div>
</body>
</html>
