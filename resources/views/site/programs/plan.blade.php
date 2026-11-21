@extends('layouts.site')

@section('title', 'الخطة التدريبية للأفراد')
@section('description', 'دورات معتمدة بمواعيد محددة لثلاثة أشهر — اختر الدورة والموعد المناسب لك وسجّل.')

@section('content')
    @include('partials.page-head', [
        'title' => 'الخطة التدريبية للأفراد',
        'crumb' => 'خطة الأفراد',
        'subtitle' => 'دورات معتمدة بمواعيد محددة من '.$start->translatedFormat('j F').' إلى '.$end->translatedFormat('j F Y').' — اختر الدورة المناسبة لك وسجّل مقعدك.',
    ])

    <section class="section">
        <div class="container">
            <div class="plan-nav">
                <a href="{{ route('programs.plan', ['from' => $prev]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-right"></i> الأشهر السابقة</a>
                <span class="plan-range"><i class="fa-regular fa-calendar"></i> {{ $start->translatedFormat('F') }} – {{ $end->translatedFormat('F Y') }} · {{ $total }} {{ $total === 1 ? 'دورة' : 'دورات' }}</span>
                <a href="{{ route('programs.plan', ['from' => $next]) }}" class="btn btn-outline btn-sm">الأشهر التالية <i class="fa-solid fa-arrow-left"></i></a>
            </div>

            @foreach ($months as $month)
                <div class="plan-month">
                    <h2><span>{{ $month['label'] }}</span></h2>
                    @if ($month['programs']->isEmpty())
                        <div class="empty">تُعلن دورات هذا الشهر قريبًا.</div>
                    @else
                        <div class="plan-list">
                            @foreach ($month['programs'] as $program)
                                @include('site.programs.plan-row')
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($upcoming->isNotEmpty())
                <div class="plan-month">
                    <h2><span>دورات تُحدَّد مواعيدها قريبًا</span></h2>
                    <div class="plan-list">
                        @foreach ($upcoming as $program)
                            @include('site.programs.plan-row')
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="section alt">
        <div class="container">
            <div class="section-head"><h2>رحلتك معنا من التسجيل إلى الشهادة</h2><div class="divider"></div></div>
            <div class="plan-journey">
                @foreach ([
                    ['fa-regular fa-hand-pointer', 'اختر دورتك', 'من الخطة المعتمدة، وكل دورة برقم اعتماد واضح.'],
                    ['fa-regular fa-pen-to-square', 'سجّل', 'من زر «سجّل الآن» أو تواصل معنا عبر واتساب.'],
                    ['fa-solid fa-file-invoice', 'تأكيد وفاتورة', 'تصلك رسالة تأكيد التسجيل مع الفاتورة.'],
                    ['fa-regular fa-bell', 'مواعيد وتذكير', 'تصلك مواعيد الدورة، وتذكير قبلها وفي يومها.'],
                    ['fa-solid fa-chalkboard-user', 'التدريب', 'مدرب معتمد وحقيبة تدريبية ومتابعة لجودة التدريب.'],
                    ['fa-solid fa-award', 'الشهادة', 'شهادة معتمدة وكود خصم لدورتك القادمة.'],
                ] as [$icon, $title, $desc])
                    <div class="step">
                        <div class="ico"><i class="{{ $icon }}"></i></div>
                        <b>{{ $loop->iteration }}. {{ $title }}</b>
                        <p>{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
