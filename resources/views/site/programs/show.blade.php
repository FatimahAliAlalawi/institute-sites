@extends('layouts.site')

@section('title', $program->title)
@section('description', $program->summary)
@if ($program->image)
    @section('og_image', media_url($program->image))
@endif

@section('content')
    <section class="page-head">
        <div class="container">
            <div class="crumbs">
                <a href="{{ route('home') }}">الرئيسية</a> /
                <a href="{{ route('programs.index') }}">البرامج</a> /
                <a href="{{ route('programs.index', ['category' => $program->category]) }}">{{ $program->category_label }}</a>
            </div>
            <h1>{{ $program->title }}</h1>
            @if ($program->summary)<p>{{ $program->summary }}</p>@endif
        </div>
    </section>

    <section class="section">
        <div class="container detail">
            <div>
                <div class="info-boxes">
                    @if ($program->target_audience)
                        <div class="info-box"><small>الفئة المستهدفة</small><b>{{ $program->target_audience }}</b></div>
                    @endif
                    @if ($program->duration)
                        <div class="info-box"><small>مدة التنفيذ</small><b>{{ $program->duration }}</b></div>
                    @endif
                    @if ($program->seats)
                        <div class="info-box"><small>المقاعد</small><b>{{ $program->is_full ? 'مكتملة' : ($program->seats_left <= 5 ? 'باقي '.$program->seats_left : $program->seats.' مقعدًا') }}</b></div>
                    @endif
                </div>

                @if ($program->image)
                    <img src="{{ media_url($program->image) }}" alt="{{ $program->title }}" style="border-radius:20px;margin-bottom:24px;width:100%">
                @endif

                @if ($program->description)
                    <div class="panel">
                        <span class="pill-title">الفكرة العامة</span>
                        <p class="prose">{{ $program->description }}</p>
                    </div>
                @endif

                @if ($program->goal)
                    <div class="panel">
                        <span class="pill-title">الهدف من البرنامج</span>
                        <p class="prose">{{ $program->goal }}</p>
                    </div>
                @endif

                @if ($program->steps_list)
                    <div class="panel">
                        <h2>مسار التنفيذ</h2>
                        <div class="timeline">
                            @foreach ($program->steps_list as $step)
                                <div class="row">
                                    <div class="num">{{ $loop->iteration }}</div>
                                    <div>
                                        <h4>{{ $step['title'] }}</h4>
                                        @if ($step['desc'])<p>{{ $step['desc'] }}</p>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($program->outcomes_list)
                    <div class="panel">
                        <h2>المخرجات المتوقعة</h2>
                        <ul class="check-list">
                            @foreach ($program->outcomes_list as $outcome)<li>{{ $outcome }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <aside class="sidebar">
                <div class="panel">
                    <h3>تفاصيل البرنامج</h3>
                    <dl>
                        <div><dt>التصنيف</dt><dd>{{ $program->category_label }}</dd></div>
                        @if ($program->hours)<div><dt>الساعات</dt><dd>{{ $program->hours }}</dd></div>@endif
                        @if ($program->duration)<div><dt>المدة</dt><dd>{{ $program->duration }}</dd></div>@endif
                        @if ($program->schedule_days)<div><dt>الأيام</dt><dd>{{ $program->schedule_days }}</dd></div>@endif
                        @if ($program->schedule_time)<div><dt>الوقت</dt><dd>{{ $program->schedule_time }}</dd></div>@endif
                        @if ($program->trainer_name)<div><dt>المدرب</dt><dd>{{ $program->trainer_name }}</dd></div>@endif
                        <div><dt>نوع التدريب</dt><dd>{{ $program->mode }}</dd></div>
                        @if ($program->location)<div><dt>المكان</dt><dd>{{ $program->location }}</dd></div>@endif
                        @if ($program->start_date)<div><dt>تاريخ البدء</dt><dd>{{ $program->start_date->translatedFormat('j F Y') }}</dd></div>@endif
                        @if ($program->has_offer)
                            <div><dt>الرسوم</dt><dd><b>{{ $program->offer_price_label }}</b> <s class="old-price">{{ $program->price_label }}</s> @if ($program->discount_percent)<span class="chip free">خصم {{ $program->discount_percent }}%</span>@endif</dd></div>
                        @elseif ($program->price_label)
                            <div><dt>الرسوم</dt><dd>{{ $program->price_label }}</dd></div>
                        @endif
                        @if ($program->offer_ends_at && $program->offer_ends_at->copy()->endOfDay()->isFuture())
                            <div><dt>آخر موعد للتسجيل</dt><dd>{{ $program->offer_ends_at->translatedFormat('j F Y') }}</dd></div>
                        @endif
                        @if ($program->accreditation_no)<div><dt>رقم الاعتماد</dt><dd dir="ltr" style="text-align:right">{{ $program->accreditation_no }}</dd></div>@endif
                        @if ($program->certificate)<div><dt>الشهادة</dt><dd>{{ $program->certificate }}</dd></div>@endif
                    </dl>
                    @if ($program->can_register)
                        @if ($program->external_link)
                            <a href="{{ $program->external_link }}" target="_blank" rel="noopener" class="btn btn-primary btn-block">سجّل الآن</a>
                        @else
                            <a href="{{ route('register', ['program' => $program->id]) }}" class="btn btn-primary btn-block">سجّل الآن</a>
                        @endif
                    @else
                        <div class="alert alert-error" style="margin:0;text-align:center">{{ $program->is_full ? 'اكتملت المقاعد — تواصل معنا للدورة القادمة' : 'التسجيل مغلق حاليًا' }}</div>
                    @endif
                    @if ($program->session_dates || $program->start_date)
                        <a href="{{ route('programs.ics', $program) }}" class="btn btn-outline btn-block" style="margin-top:10px"><i class="fa-regular fa-calendar-plus"></i> أضف أيام الدورة لتقويمك</a>
                    @endif
                    @if (setting('whatsapp'))
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp')) }}?text={{ rawurlencode('استفسار عن برنامج: '.$program->title) }}" target="_blank" rel="noopener" class="btn btn-outline btn-block" style="margin-top:10px">
                            <i class="fa-brands fa-whatsapp"></i> استفسر عبر واتساب
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section alt">
            <div class="container">
                <div class="section-head"><h2>برامج مشابهة</h2><div class="divider"></div></div>
                <div class="grid-3">
                    @foreach ($related as $program)
                        @include('partials.program-card')
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
