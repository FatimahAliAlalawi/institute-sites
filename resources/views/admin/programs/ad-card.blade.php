@php
    $logo = media_url(setting('logo', 'assets/img/logo.png'));
    $primary = setting('color_primary', '#32255D');
    $secondary = setting('color_secondary', '#1E99D1');
    $approved = $program->ad_status === 'approved';
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>بطاقة الإعلان — {{ $program->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f5; font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif; color: #1d1b2c; }
        .bar { max-width: 820px; margin: 20px auto 0; display: flex; gap: 8px; flex-wrap: wrap; }
        .bar a, .bar button { font: inherit; font-size: 14px; padding: 8px 14px; border-radius: 10px; border: 1px solid #cfd3e0; background: #fff; color: {{ $primary }}; cursor: pointer; text-decoration: none; }
        .bar .primary { background: {{ $primary }}; color: #fff; border-color: {{ $primary }}; }
        .sheet { position: relative; max-width: 820px; margin: 14px auto 30px; background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 10px 30px rgba(30, 20, 80, .08); }
        .head { background: linear-gradient(135deg, {{ $primary }}, {{ $secondary }}); color: #fff; padding: 22px 28px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .head img { height: 54px; background: #fff; border-radius: 10px; padding: 6px 10px; }
        .head small { opacity: .85; display: block; }
        .head h1 { margin: 2px 0 0; font-size: 22px; }
        .status { margin: 18px 28px 0; padding: 10px 14px; border-radius: 10px; font-weight: 700; font-size: 14px; }
        .status.ok { background: #e6f6ee; color: #16794a; }
        .status.no { background: #fdecec; color: #b42318; }
        .body { padding: 18px 28px 26px; display: grid; grid-template-columns: 1fr 210px; gap: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: right; padding: 10px 8px; border-bottom: 1px solid #eceef4; vertical-align: top; font-size: 15px; }
        th { width: 38%; color: #6b6880; font-weight: 600; }
        td b { color: {{ $primary }}; }
        .missing { color: #b42318; font-weight: 600; }
        s { color: #8a879a; margin-inline-start: 6px; }
        .qr { text-align: center; border: 1px solid #eceef4; border-radius: 14px; padding: 10px; margin-bottom: 14px; }
        .qr svg { width: 100%; height: auto; display: block; }
        .qr b { display: block; font-size: 13px; margin-top: 4px; color: {{ $primary }}; }
        .qr a { font-size: 12px; color: #6b6880; }
        .qr code { font-size: 11px; color: #6b6880; direction: ltr; display: block; word-break: break-all; }
        .watermark { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; font-size: 80px; font-weight: 700; color: rgba(180, 35, 24, .09); transform: rotate(-18deg); }
        .foot { padding: 0 28px 22px; color: #6b6880; font-size: 13px; }
        @media (max-width: 640px) { .body { grid-template-columns: 1fr; } }
        @media print { body { background: #fff; } .bar { display: none; } .sheet { box-shadow: none; margin: 0; } }
    </style>
</head>
<body>
<div class="bar">
    <button class="primary" onclick="window.print()">طباعة / حفظ PDF</button>
    <a href="{{ route('admin.programs.qr', [$program, 'page']) }}">تنزيل QR التسجيل (SVG)</a>
    @if ($qrWa)<a href="{{ route('admin.programs.qr', [$program, 'wa']) }}">تنزيل QR واتساب (SVG)</a>@endif
    <a href="{{ route('admin.programs.edit', $program) }}">تعديل بيانات البرنامج</a>
</div>

<div class="sheet">
    @unless ($approved)<div class="watermark">مسودة — غير معتمد</div>@endunless
    <div class="head">
        <div>
            <small>بطاقة إعلان دورة — للمصمم وفريق التسويق</small>
            <h1>{{ $program->title }}</h1>
        </div>
        <img src="{{ $logo }}" alt="">
    </div>

    @if ($approved)
        <div class="status ok">✔ الإعلان معتمد من الإدارة — {{ $program->ad_approved_by }} بتاريخ {{ $program->ad_approved_at?->format('Y-m-d') }}. يمكن نشره.</div>
    @else
        <div class="status no">✖ الإعلان {{ $program->ad_status === 'rejected' ? 'مرفوض' : 'غير معتمد بعد' }} — لا يُنشر قبل اعتماد الإدارة (الخطوة 3 في الدليل التشغيلي).
            @if ($program->approval_note)<br>ملاحظة الإدارة: {{ $program->approval_note }}@endif
        </div>
    @endif

    <div class="body">
        <table>
            <tr><th>الاسم المعتمد للدورة</th><td><b>{{ $program->title }}</b></td></tr>
            <tr><th>رقم الاعتماد</th><td>@if ($program->accreditation_no)<span dir="ltr">{{ $program->accreditation_no }}</span>@else<span class="missing">ناقص — أضِفه قبل التصميم</span>@endif</td></tr>
            <tr><th>المدة المعتمدة</th><td>@if ($program->duration || $program->hours){{ $program->duration }}{{ $program->duration && $program->hours ? ' — ' : '' }}{{ $program->hours }}@else<span class="missing">ناقصة</span>@endif</td></tr>
            @if ($program->schedule_days || $program->schedule_time)<tr><th>الأيام والوقت</th><td>{{ $program->schedule_days }}{{ $program->schedule_days && $program->schedule_time ? ' | ' : '' }}{{ $program->schedule_time }}</td></tr>@endif
            @if ($program->trainer_name)<tr><th>المدرب</th><td>{{ $program->trainer_name }}</td></tr>@endif
            <tr><th>تاريخ البدء</th><td>{{ $program->start_date?->translatedFormat('l j F Y') ?? '—' }}</td></tr>
            <tr><th>نوع التدريب والمكان</th><td>{{ $program->mode }}{{ $program->location ? ' — '.$program->location : '' }}</td></tr>
            <tr><th>الرسوم</th><td>
                @if ($program->sale_price !== null && $program->price !== null && (float) $program->sale_price < (float) $program->price)
                    <b>{{ (float) $program->sale_price == 0 ? 'مجاني' : number_format((float) $program->sale_price, 0).' ر.س' }}</b><s>{{ $program->price_label }}</s>
                    (خصم {{ (float) $program->price > 0 ? (int) round(100 - ((float) $program->sale_price / (float) $program->price) * 100) : 0 }}%)
                @else
                    {{ $program->price_label ?? '—' }} <span style="color:#6b6880">(بدون خصم)</span>
                @endif
            </td></tr>
            <tr><th>تاريخ انتهاء العرض / التسجيل</th><td>@if ($program->offer_ends_at){{ $program->offer_ends_at->translatedFormat('j F Y') }}@else<span class="missing">غير محدد</span>@endif</td></tr>
            <tr><th>رقم التواصل</th><td>@if ($program->contact_number)<span dir="ltr">{{ $program->contact_number }}</span>@else<span class="missing">ناقص — أضِفه في البرنامج أو إعدادات الموقع</span>@endif</td></tr>
            <tr><th>رابط التسجيل</th><td><span dir="ltr">{{ route('programs.short', $program->id) }}</span></td></tr>
            @if ($program->certificate)<tr><th>الشهادة</th><td>{{ $program->certificate }}</td></tr>@endif
        </table>

        <div>
            <div class="qr">
                {!! $qrPage !!}
                <b>امسح للتسجيل</b>
                <code>{{ route('programs.short', $program->id) }}</code>
            </div>
            @if ($qrWa)
                <div class="qr">
                    {!! $qrWa !!}
                    <b>امسح للتواصل عبر واتساب</b>
                    <code>{{ route('programs.whatsapp', $program->id) }}</code>
                </div>
            @endif
        </div>
    </div>
    <div class="foot">رموز QR تعمل بروابط قصيرة من الموقع، فإن تغيّر رقم التواصل أو رابط الدورة لا تحتاج تغيير الرمز في التصاميم المنشورة.</div>
</div>
</body>
</html>
