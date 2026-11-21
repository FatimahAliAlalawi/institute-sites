@php
    $siteName = setting('site_name', 'معهد البصائر العالي للتدريب');
    $logo = media_url(setting('logo', 'assets/img/logo.png'));
    $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp'));
    $socials = [
        'social_x' => 'fa-brands fa-x-twitter',
        'social_instagram' => 'fa-brands fa-instagram',
        'social_snapchat' => 'fa-brands fa-snapchat',
        'social_tiktok' => 'fa-brands fa-tiktok',
        'social_linkedin' => 'fa-brands fa-linkedin-in',
        'social_youtube' => 'fa-brands fa-youtube',
    ];
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle.' | '.$siteName : $siteName;
    $hex = fn ($key, $default) => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) setting($key)) ? setting($key) : $default;
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="@yield('description', setting('site_description'))">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="@yield('description', setting('site_description'))">
    <meta property="og:image" content="@yield('og_image', $logo)">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="{{ $hex('color_primary', '#3F3592') }}">
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/lms.css') }}?v=2">
    <style>
        :root {
            --primary: {{ $hex('color_primary', '#3F3592') }};
            --secondary: {{ $hex('color_secondary', '#5B2EA6') }};
            --accent: {{ $hex('color_accent', '#6690C8') }};
        }
    </style>
    @stack('head')
</head>
<body>
@php
    $annEnds = setting('announcement_ends_at');
    $showAnnouncement = setting('announcement_enabled') === '1' && setting('announcement_text') && (! $annEnds || \Illuminate\Support\Carbon::parse($annEnds)->isFuture());
    $annBg = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) setting('announcement_bg')) ? setting('announcement_bg') : '#16794a';
@endphp
@if ($showAnnouncement)
    <div class="announcement" style="background:{{ $annBg }}">
        <div class="container">
            <span>{{ setting('announcement_text') }}</span>
            @if (setting('announcement_button') && setting('announcement_link'))
                <a href="{{ url(setting('announcement_link')) }}" class="btn btn-light" style="padding:5px 16px;font-size:13px">{{ setting('announcement_button') }}</a>
            @endif
        </div>
    </div>
@endif

<div class="topbar">
    <div class="container">
        <div class="items">
            @if (setting('phone'))
                <span><i class="fa-solid fa-phone"></i> <a href="tel:{{ setting('phone') }}" dir="ltr">{{ setting('phone') }}</a></span>
            @endif
            @if (setting('email'))
                <span class="hide-sm"><i class="fa-regular fa-envelope"></i> <a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></span>
            @endif
            @if (setting('address'))
                <span class="hide-sm"><i class="fa-solid fa-location-dot"></i> {{ setting('address') }}</span>
            @endif
        </div>
        <div class="items">
            @foreach ($socials as $key => $icon)
                @if (setting($key))
                    <a href="{{ setting($key) }}" target="_blank" rel="noopener" aria-label="{{ $key }}"><i class="{{ $icon }}"></i></a>
                @endif
            @endforeach
            <a href="{{ route('certificates.verify') }}" class="hide-sm"><i class="fa-solid fa-certificate"></i> التحقق من شهادة</a>
        </div>
    </div>
</div>

<header class="header">
    <div class="container">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ $logo }}" alt="{{ $siteName }}">
            @if (setting('show_name_in_header', '1') === '1')
                <span class="name">{{ $siteName }}<small>{{ setting('site_name_en') }}</small></span>
            @endif
        </a>
        <button class="menu-toggle" type="button" aria-label="القائمة" onclick="document.getElementById('nav').classList.toggle('open')">
            <i class="fa-solid fa-bars fa-xl"></i>
        </button>
        <nav class="nav" id="nav">
            @foreach (\App\Models\MenuItem::for('header') as $item)
                <a href="{{ $item->url }}" @class(['active' => $item->isActive()]) @if ($item->new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
            @endforeach
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="fa-regular fa-user"></i> لوحتي</a>
            @else
                <a href="{{ route('login') }}" @class(['active' => request()->routeIs('login', 'signup')])>دخول</a>
                <a href="{{ route('signup') }}" class="btn btn-primary">إنشاء حساب</a>
            @endauth
        </nav>
    </div>
</header>

<main>
    @if (session('status') && ! $__env->hasSection('no_flash'))
        <div class="container" style="margin-top:20px"><div class="alert alert-success" style="margin:0">{{ session('status') }}</div></div>
    @endif
    @yield('content')
</main>

<footer class="footer">
    <div class="container">
        <div class="cols">
            <div>
                <div class="logo-box"><img src="{{ $logo }}" alt="{{ $siteName }}"></div>
                <p>{{ setting('footer_text') }}</p>
                <div class="socials">
                    @foreach ($socials as $key => $icon)
                        @if (setting($key))
                            <a href="{{ setting($key) }}" target="_blank" rel="noopener" aria-label="{{ $key }}"><i class="{{ $icon }}"></i></a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div>
                <h4>روابط سريعة</h4>
                <ul>
                    @foreach (\App\Models\MenuItem::for('footer') as $item)
                        <li><a href="{{ $item->url }}" @if ($item->new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4>البرامج</h4>
                <ul>
                    @foreach (\App\Models\Program::CATEGORIES as $key => $label)
                        <li><a href="{{ route('programs.index', ['category' => $key]) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4>تواصل معنا</h4>
                <ul>
                    @if (setting('address'))<li><i class="fa-solid fa-location-dot"></i> {{ setting('address') }}</li>@endif
                    @if (setting('phone'))<li><i class="fa-solid fa-phone"></i> <a href="tel:{{ setting('phone') }}" dir="ltr">{{ setting('phone') }}</a></li>@endif
                    @if (setting('phone2'))<li><i class="fa-solid fa-phone"></i> <a href="tel:{{ setting('phone2') }}" dir="ltr">{{ setting('phone2') }}</a></li>@endif
                    @if (setting('email'))<li><i class="fa-regular fa-envelope"></i> <a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>@endif
                    @if (setting('working_hours'))<li><i class="fa-regular fa-clock"></i> {{ setting('working_hours') }}</li>@endif
                </ul>
            </div>
        </div>
        <div class="bottom">
            <span>© {{ date('Y') }} {{ $siteName }} — جميع الحقوق محفوظة</span>
            @if (setting('license_no'))<span>رقم الترخيص: {{ setting('license_no') }}</span>@endif
        </div>
    </div>
</footer>

@if ($whatsapp)
    <a class="whatsapp-float" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="واتساب"><i class="fa-brands fa-whatsapp"></i></a>
@endif

@stack('scripts')
</body>
</html>
