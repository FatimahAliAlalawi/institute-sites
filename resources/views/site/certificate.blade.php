@php
    $siteName = setting('site_name', 'معهد البصائر العالي للتدريب');
    $primary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) setting('color_primary')) ? setting('color_primary') : '#3F3592';
    $accent = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) setting('color_accent')) ? setting('color_accent') : '#6690C8';
    $verifyUrl = route('certificates.verify', ['code' => $certificate->code]);
    $body = strtr(setting('certificate_text', 'يشهد :site بأن المتدرب/ـة'), [':site' => $siteName]);
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>شهادة إتمام — {{ $certificate->user?->name }} — {{ $certificate->course?->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Amiri:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9e7f2; font-family: "IBM Plex Sans Arabic", Tahoma, sans-serif; color: #24222e; }
        .bar { max-width: 1123px; margin: 20px auto 0; display: flex; gap: 10px; justify-content: space-between; align-items: center; padding: 0 16px; flex-wrap: wrap; }
        .bar a, .bar button { background: {{ $primary }}; color: #fff; border: 0; border-radius: 999px; padding: 10px 22px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        .bar a.light { background: #fff; color: {{ $primary }}; }
        .flash { background: #e6f6ee; color: #16794a; border-radius: 12px; padding: 12px 18px; max-width: 1123px; margin: 14px auto 0; }
        .wrap { padding: 20px 16px 40px; overflow-x: auto; }
        .cert { width: 1123px; height: 794px; margin: 0 auto; background: #fff; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(30, 20, 80, .15); }
        .cert::before { content: ""; position: absolute; inset: 22px; border: 3px solid {{ $primary }}; border-radius: 6px; }
        .cert::after { content: ""; position: absolute; inset: 32px; border: 1px solid {{ $accent }}; border-radius: 4px; }
        .corner { position: absolute; width: 360px; height: 360px; border-radius: 50%; background: linear-gradient(135deg, {{ $primary }}, {{ $accent }}); opacity: .09; }
        .c1 { top: -180px; left: -160px; } .c2 { bottom: -200px; right: -140px; }
        .inner { position: relative; z-index: 1; height: 100%; padding: 70px 100px; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .inner img.logo { height: 110px; margin-bottom: 10px; }
        h1 { font-family: "Amiri", serif; font-size: 54px; color: {{ $primary }}; margin: 6px 0 4px; letter-spacing: 1px; }
        .sub { color: #6c6a7a; font-size: 18px; margin: 0 0 22px; }
        .name { font-family: "Amiri", serif; font-size: 46px; color: #1d1846; margin: 8px 0; border-bottom: 2px solid {{ $accent }}; padding: 0 40px 6px; }
        .course { font-size: 26px; font-weight: 700; color: {{ $primary }}; margin: 6px 0 0; }
        .text { font-size: 19px; color: #444; margin: 6px 0; }
        .foot { margin-top: auto; width: 100%; display: flex; justify-content: space-between; align-items: flex-end; }
        .foot .col { text-align: center; min-width: 220px; }
        .foot .line { border-top: 1.5px solid #bbb; margin-top: 46px; padding-top: 6px; font-weight: 700; }
        .foot small { color: #6c6a7a; display: block; }
        .seal { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, {{ $primary }}, {{ $accent }}); color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; box-shadow: 0 0 0 6px #fff, 0 0 0 8px {{ $accent }}; }
        .seal i { font-size: 34px; margin-bottom: 4px; }
        .code { font-size: 13px; color: #6c6a7a; margin-top: 10px; direction: ltr; }
        @media print {
            body { background: #fff; }
            .bar, .flash { display: none; }
            .wrap { padding: 0; }
            .cert { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
<div class="bar">
    <a class="light" href="{{ auth()->check() ? route('dashboard.certificates') : route('home') }}">← رجوع</a>
    <button onclick="window.print()"><i class="fa-solid fa-print"></i> طباعة / حفظ PDF</button>
</div>
@if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
<div class="wrap">
    <div class="cert">
        <div class="corner c1"></div><div class="corner c2"></div>
        <div class="inner">
            <img class="logo" src="{{ media_url(setting('logo', 'assets/img/logo.png')) }}" alt="">
            <h1>{{ setting('certificate_title', 'شهادة إتمام') }}</h1>
            <p class="sub">{{ $body }}</p>
            <div class="name">{{ $certificate->user?->name }}</div>
            <p class="text">قد أتمّ/ت بنجاح متطلبات الدورة التدريبية</p>
            <div class="course">« {{ $certificate->course?->title }} »</div>
            @if ($certificate->course?->duration_text)<p class="text">بمدة {{ $certificate->course->duration_text }}</p>@endif
            <p class="text">وتاريخ {{ $certificate->issued_at->translatedFormat('j F Y') }}</p>

            <div class="foot">
                <div class="col">
                    <div class="line">{{ $certificate->course?->instructor?->name ?? '—' }}</div>
                    <small>المدرب</small>
                </div>
                <div class="col">
                    <div class="seal"><i class="fa-solid fa-award"></i>شهادة موثّقة</div>
                    <div class="code">{{ $certificate->code }}</div>
                    <small style="direction:ltr">{{ $verifyUrl }}</small>
                </div>
                <div class="col">
                    <div class="line">{{ setting('certificate_signer', $siteName) }}</div>
                    <small>{{ setting('certificate_signer_title', 'إدارة المعهد') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
