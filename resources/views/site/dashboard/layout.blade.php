@extends('layouts.site')

@section('content')
    @include('partials.page-head', ['title' => trim($__env->yieldContent('title')), 'crumb' => 'لوحتي'])

    <section class="section" style="padding-top:40px">
        <div class="container dash">
            <aside class="dash-nav">
                @php($me = auth()->user())
                <div class="me">
                    @if ($me->avatar)
                        <img class="avatar" style="width:46px;height:46px" src="{{ media_url($me->avatar) }}" alt="">
                    @else
                        <span class="avatar" style="width:46px;height:46px">{{ $me->initials }}</span>
                    @endif
                    <div><b>{{ $me->name }}</b><div class="muted" style="font-size:13px">{{ $me->email }}</div></div>
                </div>
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])><i class="fa-solid fa-book-open-reader"></i> دوراتي</a>
                <a href="{{ route('dashboard.certificates') }}" @class(['active' => request()->routeIs('dashboard.certificates')])><i class="fa-solid fa-award"></i> شهاداتي</a>
                <a href="{{ route('dashboard.attempts') }}" @class(['active' => request()->routeIs('dashboard.attempts')])><i class="fa-regular fa-circle-question"></i> نتائج الاختبارات</a>
                <a href="{{ route('dashboard.orders') }}" @class(['active' => request()->routeIs('dashboard.orders')])><i class="fa-solid fa-receipt"></i> مدفوعاتي</a>
                <a href="{{ route('dashboard.profile') }}" @class(['active' => request()->routeIs('dashboard.profile')])><i class="fa-regular fa-id-card"></i> الملف الشخصي</a>
                <a href="{{ route('courses.index') }}"><i class="fa-solid fa-magnifying-glass"></i> تصفّح الدورات</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</button>
                </form>
            </aside>
            <div>
                @yield('dash')
            </div>
        </div>
    </section>
@endsection
