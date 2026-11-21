@extends('layouts.site')

@section('no_flash', '1')

@section('content')
    <div class="player">
        <aside class="player-side" id="playerSide">
            <div class="head">
                <h3><a href="{{ route('courses.show', $course) }}"><i class="fa-solid fa-arrow-right"></i> {{ $course->title }}</a></h3>
                @auth
                    <div class="progress"><span style="width:{{ $progress['percent'] }}%"></span></div>
                    <div class="progress-label" style="margin-bottom:0"><span>{{ $progress['done'] }} / {{ $progress['total'] }}</span><b>{{ $progress['percent'] }}%</b></div>
                    @if ($release['enabled'])
                        <div class="day-box {{ $release['remaining'] > 0 ? 'open' : 'wait' }}">
                            @if ($release['remaining'] > 0)
                                <i class="fa-solid fa-calendar-check"></i> درس اليوم متاح — أكمله ليُسجَّل حضورك
                            @elseif ($release['study_day'])
                                <i class="fa-solid fa-circle-check"></i> تم حضور اليوم ✅ الدرس التالي {{ \App\Support\DailyRelease::humanDay($release['next_day']) }}
                            @else
                                <i class="fa-solid fa-mug-hot"></i> اليوم ليس يوم دراسة — الدرس التالي {{ \App\Support\DailyRelease::humanDay($release['next_day']) }}
                            @endif
                            <small>أيام دراستك: {{ \App\Support\DailyRelease::label($release['days']) }}</small>
                        </div>
                    @endif
                    @if ($certificate)
                        <a href="{{ route('certificates.show', $certificate->code) }}" class="btn btn-outline btn-block" style="margin-top:10px;padding:8px"><i class="fa-solid fa-award"></i> شهادتك جاهزة</a>
                    @endif
                @endauth
            </div>
            @php $canAccessAll = auth()->user()?->isEnrolled($course) ?? false; @endphp
            @foreach ($curriculum as $topic)
                <div class="topic-title">{{ $topic->title }}</div>
                @foreach ($topic->items as $item)
                    @php
                        $m = $item['model'];
                        $isLesson = $item['type'] === 'lesson';
                        $isLive = $item['type'] === 'live';
                        $done = $isLesson ? in_array($m->id, $progress['completed_lessons']) : ($isLive ? $m->state === 'ended' : in_array($m->id, $progress['passed_quizzes']));
                        $open = $canAccessAll ? \App\Support\DailyRelease::isUnlocked($release, $item['type'], $m->id) : ($isLesson && $m->is_preview);
                        $url = ($open || $canAccessAll) ? route(\App\Models\Course::itemRoute($item['type']), [$course, $m]) : route('courses.show', $course);
                    @endphp
                    <a href="{{ $url }}" @class(['item', 'current' => $currentType === $item['type'] && $currentId === $m->id, 'locked' => ! $open])>
                        <i class="fa-regular {{ $isLive ? 'fa-calendar-check' : ($isLesson ? ($m->video_url ? 'fa-circle-play' : 'fa-file-lines') : 'fa-circle-question') }}"></i>
                        <span>{{ $m->title }}@if ($isLive)<small class="muted" style="display:block;font-size:12px">{{ $m->starts_at->translatedFormat('D j M — g:i a') }}</small>@endif</span>
                        <span class="st">
                            @if (! $open)<i class="fa-solid fa-lock" title="{{ $canAccessAll ? 'يُتاح لاحقًا حسب جدول الدورة' : 'للمشتركين فقط' }}"></i>@elseif ($done)<i class="fa-solid fa-circle-check"></i>@else<i class="fa-regular fa-circle"></i>@endif
                        </span>
                    </a>
                @endforeach
            @endforeach
        </aside>

        <div class="player-main">
            <div class="inner">
                <button class="btn btn-outline player-toggle" type="button" onclick="document.getElementById('playerSide').classList.toggle('open')"><i class="fa-solid fa-list"></i> محتوى الدورة</button>
                @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
                @yield('player')
            </div>
        </div>
    </div>
@endsection
