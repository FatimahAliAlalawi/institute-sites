@extends('layouts.site')

@section('title', 'تسجيل الدخول')

@section('content')
    @include('partials.page-head', ['title' => 'تسجيل الدخول', 'subtitle' => 'ادخل إلى حسابك لمتابعة دوراتك'])

    <section class="section">
        <div class="container auth-wrap">
            <div class="form-card">
                <form class="form" method="post" action="{{ route('login.attempt') }}">
                    @csrf
                    <div>
                        <label for="email">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" dir="ltr" style="text-align:right">
                        @error('email')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="password">كلمة المرور</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <label class="check-row"><input type="checkbox" name="remember" value="1" checked> تذكرني</label>
                    <button class="btn btn-primary btn-block" type="submit">دخول</button>
                </form>
                <div class="switch">ليس لديك حساب؟ <a href="{{ route('signup') }}">أنشئ حسابًا مجانًا</a></div>
                <div class="switch" style="font-size:13.5px">نسيت كلمة المرور؟ تواصل معنا عبر <a href="{{ route('contact') }}">صفحة التواصل</a> لإعادة تعيينها.</div>
            </div>
        </div>
    </section>
@endsection
