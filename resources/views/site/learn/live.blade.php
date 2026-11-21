@extends('site.learn.player')

@section('title', $session->title.' — '.$course->title)

@section('player')
    <h1 style="font-size:26px;color:var(--primary)"><i class="fa-solid fa-video"></i> {{ $session->title }}</h1>

    <div class="result-box" style="text-align:right">
        <div class="quiz-info" style="margin-top:0">
            <div><b>{{ $session->starts_at->translatedFormat('l') }}</b><small>{{ $session->starts_at->translatedFormat('j F Y') }}</small></div>
            <div><b dir="ltr">{{ $session->starts_at->format('g:i A') }}</b><small>وقت البدء (بتوقيت السعودية)</small></div>
            <div><b>{{ $session->duration }} د</b><small>المدة</small></div>
            <div><b>{{ ['upcoming' => 'قادمة', 'open' => 'مباشر الآن', 'ended' => 'انتهت'][$session->state] }}</b><small>الحالة</small></div>
        </div>

        @if ($session->state === 'upcoming')
            <p style="font-size:18px;margin:10px 0">تبدأ المحاضرة بعد <b id="countdown" data-start="{{ $session->starts_at->timestamp }}">{{ $session->starts_at->diffForHumans(['parts' => 2]) }}</b></p>
            <p class="muted">زر الانضمام يُفتح قبل الموعد بـ {{ \App\Models\LiveSession::OPEN_BEFORE_MINUTES }} دقيقة. احفظ الموعد في تقويمك.</p>
            <button class="btn btn-outline" disabled><i class="fa-solid fa-lock"></i> الانضمام غير متاح بعد</button>
        @elseif ($session->state === 'open')
            @if ($session->join_url)
                <a href="{{ route('learn.live.join', [$course, $session]) }}" target="_blank" rel="noopener" class="btn btn-primary" style="font-size:18px;padding:14px 34px">
                    <i class="fa-solid fa-video"></i> انضم الآن عبر Zoom
                </a>
                @if ($session->password)<p class="muted" style="margin:10px 0 0">رمز الدخول (إن طُلب): <b dir="ltr">{{ $session->password }}</b></p>@endif
            @else
                <div class="alert alert-error">لم يُضف رابط المحاضرة بعد — تواصل مع المعهد.</div>
            @endif
        @else
            <p style="font-size:18px">انتهت المحاضرة.</p>
            @if ($session->recording_url)
                <a href="{{ $session->recording_url }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-circle-play"></i> مشاهدة التسجيل</a>
            @else
                <p class="muted">سيُضاف تسجيل المحاضرة هنا إن توفر.</p>
            @endif
        @endif

        @if ($attended)<p style="margin:14px 0 0"><span class="pill done"><i class="fa-solid fa-check"></i> تم تسجيل حضورك</span></p>@endif
    </div>

    @if ($session->description)
        <div class="lesson-content"><div class="prose">{{ $session->description }}</div></div>
    @endif

    @include('site.learn.nav', ['slot' => new \Illuminate\Support\HtmlString('')])

    @if ($session->state === 'upcoming')
        @push('scripts')
            <script>
                (function () {
                    var el = document.getElementById('countdown'), start = +el.dataset.start * 1000;
                    function tick() {
                        var s = Math.max(0, Math.floor((start - Date.now()) / 1000));
                        var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), sec = s % 60;
                        el.textContent = (d ? d + ' يوم ' : '') + h + ' ساعة ' + m + ' دقيقة ' + sec + ' ثانية';
                        if (s <= {{ \App\Models\LiveSession::OPEN_BEFORE_MINUTES * 60 }}) { location.reload(); return; }
                        setTimeout(tick, 1000);
                    }
                    tick();
                })();
            </script>
        @endpush
    @endif
@endsection
