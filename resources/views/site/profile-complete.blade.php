@extends('layouts.site')

@section('title', 'إكمال بيانات التسجيل')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@php($r = $registration)

@section('content')
    @include('partials.page-head', ['title' => 'إكمال بيانات التسجيل', 'subtitle' => $r->program?->title])

    <section class="section">
        <div class="container" style="max-width:760px">
            @if (session('saved'))
                <div class="form-card center" style="margin-bottom:20px">
                    <div style="font-size:60px;color:#16794a"><i class="fa-regular fa-circle-check"></i></div>
                    <h2 style="color:var(--primary)">تم حفظ بياناتك</h2>
                    <p class="muted">شكرًا {{ explode(' ', $r->name)[0] }}. بياناتك تُستخدم في الشهادة والتسجيل في المؤسسة العامة للتدريب التقني والمهني. إذا احتجت تعديلها افتح نفس الرابط.</p>
                </div>
            @endif
            <div class="form-card">
                <p style="margin-top:0">
                    @if ($r->profile_done_at && ! session('saved'))
                        بياناتك مكتملة. تقدر تعدّلها هنا إذا فيه خطأ.
                    @else
                        تم تسجيلك في الدورة. فضلًا أكمل بياناتك كما في الهوية، لأنها تُكتب في الشهادة.
                    @endif
                </p>
                @if ($errors->any())
                    <div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                @endif
                <form class="form" method="post" action="{{ route('profile.complete.store', $token) }}">
                    @csrf
                    <div class="fields-grid">
                        <div class="w-full">
                            <label for="f-name">الاسم الرباعي كما في الهوية <span class="req">*</span></label>
                            <input type="text" id="f-name" name="name" value="{{ old('name', $r->name) }}" maxlength="190" required autocomplete="name">
                        </div>
                        <div>
                            <label for="f-nid">رقم الهوية / الإقامة <span class="req">*</span></label>
                            <input type="text" id="f-nid" name="national_id" value="{{ old('national_id', $r->national_id) }}" inputmode="numeric" maxlength="10" dir="ltr" required>
                        </div>
                        <div>
                            <label for="f-birth">تاريخ الميلاد (ميلادي) <span class="req">*</span></label>
                            <input type="date" id="f-birth" name="birth_date" value="{{ old('birth_date', $r->birth_date?->toDateString()) }}" required>
                        </div>
                        <div>
                            <label>الجنس <span class="req">*</span></label>
                            <div class="checks">
                                @foreach (['ذكر', 'أنثى'] as $g)
                                    <label><input type="radio" name="gender" value="{{ $g }}" @checked(old('gender', $r->gender) === $g) required> {{ $g }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label for="f-phone">رقم الجوال للتواصل <span class="req">*</span></label>
                            <input type="tel" id="f-phone" name="phone" value="{{ old('phone', $r->phone) }}" dir="ltr" required autocomplete="tel">
                        </div>
                        <div class="w-full">
                            <label for="f-email">البريد الإلكتروني <span class="req">*</span></label>
                            <input type="email" id="f-email" name="email" value="{{ old('email', $r->email) }}" dir="ltr" required autocomplete="email">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">حفظ البيانات</button>
                </form>
            </div>
        </div>
    </section>
@endsection
