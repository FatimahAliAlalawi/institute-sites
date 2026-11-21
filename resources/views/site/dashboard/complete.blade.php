@extends('layouts.site')

@section('title', 'أكمل بياناتك')

@section('content')
    @include('partials.page-head', ['title' => 'أكمل بياناتك', 'subtitle' => 'خطوة واحدة قبل متابعة دوراتك'])

    <section class="section">
        <div class="container auth-wrap" style="max-width:640px">
            <div class="form-card">
                <p style="margin-top:0">مرحبًا {{ $user->name }}، نحتاج بعض البيانات لإكمال ملفك التدريبي وإصدار الشهادة باسمك الصحيح. تُكتب مرة واحدة فقط.</p>
                @if ($errors->any())
                    <div class="alert alert-error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                @endif
                <form class="form" method="post" action="{{ route('profile.complete.save') }}">
                    @csrf
                    @method('PUT')
                    <div class="fields-grid">
                        @foreach ($fields as $field)
                            @include('partials.form-field', ['field' => $field, 'default' => $field->key === 'birth_date' ? $user->birth_date?->format('Y-m-d') : $user->{$field->key}])
                        @endforeach
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">حفظ ومتابعة</button>
                </form>
                <form method="post" action="{{ route('logout') }}" class="switch">
                    @csrf
                    <button type="submit" class="link-btn" style="background:none;border:0;color:inherit;text-decoration:underline;cursor:pointer">تسجيل الخروج</button>
                </form>
            </div>
        </div>
    </section>
@endsection
