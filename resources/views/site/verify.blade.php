@extends('layouts.site')

@section('title', 'التحقق من شهادة')

@section('content')
    @include('partials.page-head', ['title' => 'التحقق من شهادة', 'subtitle' => 'أدخل رمز الشهادة المطبوع أسفلها للتأكد من صحتها'])

    <section class="section">
        <div class="container" style="max-width:720px">
            <form class="form-card" method="get" action="{{ route('certificates.verify') }}" style="display:flex;gap:10px;flex-wrap:wrap">
                <input type="text" name="code" value="{{ $code }}" placeholder="مثال: AB12-CD34" dir="ltr" style="flex:1;min-width:200px;text-align:center;font-size:18px;letter-spacing:2px" required>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تحقق</button>
            </form>

            @if ($code !== '')
                @if ($certificate)
                    <div class="panel" style="margin-top:24px;border-color:#bfe6d1;background:#f3fbf7">
                        <h2 style="color:#16794a"><i class="fa-solid fa-circle-check"></i> شهادة صحيحة وموثّقة</h2>
                        <dl class="sidebar" style="position:static">
                            <div style="display:grid;gap:8px">
                                <div><span class="muted">اسم المتدرب:</span> <b>{{ $certificate->user?->name }}</b></div>
                                <div><span class="muted">الدورة:</span> <b>{{ $certificate->course?->title }}</b></div>
                                <div><span class="muted">تاريخ الإصدار:</span> <b>{{ $certificate->issued_at->format('Y-m-d') }}</b></div>
                                <div><span class="muted">رمز الشهادة:</span> <b dir="ltr">{{ $certificate->code }}</b></div>
                            </div>
                        </dl>
                        <a class="btn btn-outline" style="margin-top:14px" href="{{ route('certificates.show', $certificate->code) }}" target="_blank">عرض الشهادة</a>
                    </div>
                @else
                    <div class="alert alert-error" style="margin-top:24px"><i class="fa-solid fa-circle-xmark"></i> لا توجد شهادة بهذا الرمز. تأكد من كتابته بشكل صحيح.</div>
                @endif
            @endif
        </div>
    </section>
@endsection
