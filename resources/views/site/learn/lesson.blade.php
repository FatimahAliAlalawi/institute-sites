@extends('site.learn.player')

@section('title', $lesson->title.' — '.$course->title)

@section('player')
    <h1 style="font-size:26px;color:var(--primary)">{{ $lesson->title }}</h1>

    @if ($locked)
        <div class="result-box" style="padding:40px">
            <div style="font-size:54px;color:var(--accent)"><i class="fa-solid fa-lock"></i></div>
            <h2>هذا الدرس لم يُتح بعد</h2>
            <p class="muted" style="margin:0">هذه دورة مسجلة تُفتح دروسها تدريجيًا: {{ $release['per_day'] }} {{ $release['per_day'] > 1 ? 'دروس' : 'درس' }} في كل يوم دراسة ({{ \App\Support\DailyRelease::label($release['days']) }}).</p>
            <p style="margin:10px 0 0"><b>يُتاح {{ ($release['remaining'] > 0) ? 'بعد إكمال الدرس السابق' : \App\Support\DailyRelease::humanDay($release['next_day']) }}</b></p>
        </div>
    @elseif ($video = $lesson->video)
        <div class="video">
            @if ($video['type'] === 'iframe')
                <iframe src="{{ $video['src'] }}" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture; fullscreen" title="{{ $lesson->title }}"></iframe>
            @else
                <video src="{{ $video['src'] }}" controls controlsList="nodownload" preload="metadata"></video>
            @endif
        </div>
    @endif

    @if (! $locked && ($lesson->content || $lesson->attachment))
        <div class="lesson-content">
            @if ($lesson->content)<div class="prose">{{ $lesson->content }}</div>@endif
            @if ($lesson->attachment)
                <a class="btn btn-outline" style="margin-top:16px" href="{{ route('learn.attachment', [$course, $lesson]) }}">
                    <i class="fa-solid fa-paperclip"></i> تحميل المرفق: {{ $lesson->attachment_name ?: 'ملف الدرس' }}
                </a>
            @endif
        </div>
    @endif

    @php $slot = ''; @endphp
    @if ($enrolled && $locked)
        @php $slot = '<span class="pill pending" style="padding:8px 18px;font-size:14px"><i class="fa-solid fa-lock"></i> مقفل حاليًا</span>'; @endphp
    @elseif ($enrolled)
        @php
            $slot = $isDone
                ? '<span class="pill done" style="padding:8px 18px;font-size:14px"><i class="fa-solid fa-check"></i> تم إكمال الدرس</span>'
                : '<form method="post" action="'.e(route('learn.complete', [$course, $lesson])).'">'.csrf_field().'<button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> إكمال الدرس والمتابعة</button></form>';
        @endphp
    @else
        @php $slot = '<a class="btn btn-primary" href="'.e(route('courses.show', $course)).'">اشترك لمتابعة الدورة</a>'; @endphp
    @endif
    @include('site.learn.nav', ['slot' => new \Illuminate\Support\HtmlString($slot)])
@endsection
