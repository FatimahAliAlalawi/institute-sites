@extends('layouts.site')

@php($label = \App\Models\ProgramTestResult::KINDS[$kind])

@section('title', $label)

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    @include('partials.page-head', ['title' => $label, 'subtitle' => $program->title])

    <section class="section">
        <div class="container" style="max-width:820px">
            @if ($result)
                <div class="form-card center">
                    <div style="font-size:60px;color:#16794a"><i class="fa-regular fa-circle-check"></i></div>
                    <h2 style="color:var(--primary)">تم تسجيل {{ $label }}</h2>
                    @if ($kind === 'pre')
                        <p class="muted">شكرًا {{ $registration->name }}. وصلتنا إجاباتك، ونتيجتك تظهر بعد الاختبار البعدي لتشوف مقدار تقدّمك.</p>
                    @else
                        <p style="font-size:34px;font-weight:700;color:var(--primary);margin:6px 0">{{ $result->score }}%</p>
                        @if ($preResult)
                            @php($diff = $result->score - $preResult->score)
                            <p>درجتك قبل الدورة: <b>{{ $preResult->score }}%</b>@if ($diff > 0) — تحسّنت بمقدار <b style="color:#16794a">{{ $diff }} نقطة</b> 👏 @endif</p>
                        @endif
                        <p class="muted">شكرًا {{ $registration->name }} على إتمام الاختبار.</p>
                    @endif
                </div>
            @elseif (! $open)
                <div class="form-card center">
                    <div style="font-size:54px;color:var(--accent)"><i class="fa-solid fa-lock"></i></div>
                    <h2 style="color:var(--primary)">الاختبار مغلق حاليًا</h2>
                    <p class="muted">يُفتح في وقته من إدارة الدورة. افتح نفس الرابط لاحقًا.</p>
                </div>
            @else
                <div class="form-card">
                    <p style="margin-top:0">أهلًا <b>{{ $registration->name }}</b>. {{ $kind === 'pre' ? 'الهدف معرفة مستواك قبل الدورة، وليس نجاحًا أو رسوبًا. أجب حسب معرفتك الحالية.' : 'نقيس مقدار استفادتك من الدورة.' }} الإرسال مرة واحدة فقط.</p>
                    @if ($errors->any())
                        <div class="alert alert-error">{{ $errors->first() }}</div>
                    @endif
                    <form method="post" action="{{ route('program-test.store', [$token, $kind]) }}" onsubmit="return confirm('تسليم الإجابات؟')">
                        @csrf
                        @foreach ($questions as $q)
                            <fieldset class="question" style="border:1px solid #e3e1ec;border-radius:14px;padding:16px;margin:0 0 14px">
                                <legend style="font-weight:700;padding:0 8px">{{ $loop->iteration }}. {{ $q->question }}</legend>
                                @foreach ($q->options as $i => $option)
                                    <label class="option" style="display:block;padding:6px 0">
                                        <input type="radio" name="answers[{{ $q->id }}]" value="{{ $i }}" required @checked((string) old('answers.'.$q->id, '') === (string) $i)>
                                        {{ $option }}
                                    </label>
                                @endforeach
                            </fieldset>
                        @endforeach
                        <button class="btn btn-primary btn-block" type="submit">تسليم الإجابات</button>
                    </form>
                </div>
            @endif
        </div>
    </section>
@endsection
