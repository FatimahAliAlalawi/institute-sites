@extends('site.dashboard.layout')

@section('title', 'دوراتي')

@section('dash')
    <div class="dash-stats">
        <div><b>{{ $stats['active'] }}</b><span>دورة مسجّل بها</span></div>
        <div><b>{{ $stats['completed'] }}</b><span>دورة مكتملة</span></div>
        <div><b>{{ $stats['certificates'] }}</b><span>شهادة</span></div>
    </div>

    @if ($upcoming->isNotEmpty())
        <div class="panel" style="border-color:var(--accent)">
            <h3 style="margin-top:0"><i class="fa-solid fa-video"></i> محاضراتك المباشرة القادمة</h3>
            @foreach ($upcoming as $s)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--line);flex-wrap:wrap">
                    <span><b>{{ $s->title }}</b> <span class="muted">— {{ $s->course?->title }}</span><br><span class="muted" style="font-size:13px">{{ $s->starts_at->translatedFormat('l j F — g:i a') }}</span></span>
                    <a class="btn {{ $s->state === 'open' ? 'btn-primary' : 'btn-outline' }}" style="padding:6px 16px" href="{{ route('learn.live', [$s->course, $s]) }}">{{ $s->state === 'open' ? 'انضم الآن' : 'التفاصيل' }}</a>
                </div>
            @endforeach
        </div>
    @endif

    @forelse ($enrollments as $enrollment)
        @php($course = $enrollment->course)
        <div class="my-course">
            <div class="thumb">
                @if ($course->thumbnail)<img src="{{ media_url($course->thumbnail) }}" alt="">@else{{ $course->title }}@endif
            </div>
            <div>
                <h3><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a></h3>
                @if ($enrollment->status === 'active')
                    <div class="progress"><span style="width:{{ $enrollment->progress['percent'] }}%"></span></div>
                    <div class="progress-label" style="margin-bottom:0">
                        <span>{{ $enrollment->progress['done'] }} من {{ $enrollment->progress['total'] }} مكتمل</span>
                        <b>{{ $enrollment->progress['percent'] }}%</b>
                    </div>
                @endif
                @if (isset($enrollment->release) && ! $enrollment->completed_at)
                    <div class="day-box {{ $enrollment->release['remaining'] > 0 ? 'open' : 'wait' }}" style="margin:8px 0 0">
                        @if ($enrollment->release['remaining'] > 0)
                            <i class="fa-solid fa-calendar-check"></i> درس اليوم متاح الآن
                        @elseif ($enrollment->release['study_day'])
                            <i class="fa-solid fa-circle-check"></i> تم حضور اليوم ✅ — الدرس التالي {{ \App\Support\DailyRelease::humanDay($enrollment->release['next_day']) }}
                        @else
                            <i class="fa-solid fa-mug-hot"></i> اليوم إجازة — الدرس التالي {{ \App\Support\DailyRelease::humanDay($enrollment->release['next_day']) }}
                        @endif
                        <small>الحضور: {{ $enrollment->attendance['present'] }} يوم · الغياب: {{ $enrollment->attendance['absent'] }} يوم · نسبة الحضور {{ $enrollment->attendance['rate'] }}%</small>
                    </div>
                @endif
                <div style="margin-top:6px">
                    @if ($enrollment->completed_at)
                        <span class="pill done"><i class="fa-solid fa-check"></i> مكتملة</span>
                    @else
                        <span class="pill {{ $enrollment->status }}">{{ $enrollment->status_label }}</span>
                    @endif
                </div>
            </div>
            <div>
                @if ($enrollment->status === 'active')
                    <a href="{{ route('learn.start', $course) }}" class="btn btn-primary">{{ $enrollment->progress['done'] ? 'تابع' : 'ابدأ' }}</a>
                @elseif ($enrollment->status === 'pending')
                    <a href="{{ route('courses.checkout', $course) }}" class="btn btn-outline">تفاصيل الدفع</a>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">
            لم تشترك في أي دورة بعد.
            <div style="margin-top:14px"><a href="{{ route('courses.index') }}" class="btn btn-primary">تصفّح الدورات</a></div>
        </div>
    @endforelse
@endsection
