@extends('site.learn.player')

@section('title', 'نتيجة: '.$quiz->title)

@section('player')
    <div @class(['result-box', 'pass' => $attempt->passed, 'fail' => ! $attempt->passed])>
        <div class="score">{{ $attempt->score }}%</div>
        <h2>{{ $attempt->passed ? 'مبروك! اجتزت الاختبار 🎉' : 'لم تجتز الاختبار هذه المرة' }}</h2>
        <p class="muted">{{ $attempt->earned_points }} من {{ $attempt->total_points }} نقطة · درجة النجاح {{ $quiz->pass_mark }}%</p>
        @if (session('certificate_code'))
            <a class="btn btn-primary" href="{{ route('certificates.show', session('certificate_code')) }}"><i class="fa-solid fa-award"></i> أكملت الدورة! اعرض شهادتك</a>
        @endif
        @if (! $attempt->passed && $attemptsLeft !== 0)
            <a class="btn btn-primary" href="{{ route('learn.quiz', [$course, $quiz, 'start' => 1]) }}"><i class="fa-solid fa-rotate-right"></i> إعادة المحاولة</a>
        @endif
    </div>

    @if ($quiz->show_answers)
        @foreach ($quiz->questions as $q)
            @php
                $given = $attempt->answers[$q->id]['given'] ?? [];
                $right = $attempt->answers[$q->id]['correct'] ?? false;
            @endphp
            <div class="question">
                <h4>
                    <span class="num">{{ $loop->iteration }}.</span> {{ $q->question }}
                    <i class="fa-solid {{ $right ? 'fa-circle-check' : 'fa-circle-xmark' }}" style="color:{{ $right ? '#16794a' : '#c62828' }}"></i>
                </h4>
                @foreach ($q->options as $i => $option)
                    <div @class(['option', 'right' => ! empty($option['correct']), 'wrong' => in_array($i, $given) && empty($option['correct'])])>
                        <i class="fa-regular {{ in_array($i, $given) ? 'fa-square-check' : 'fa-square' }}"></i> {{ $option['text'] }}
                    </div>
                @endforeach
                @if ($q->explanation)<p class="muted" style="margin:8px 0 0"><b>التوضيح:</b> {{ $q->explanation }}</p>@endif
            </div>
        @endforeach
    @endif

    @include('site.learn.nav', ['slot' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline" href="'.e(route('learn.quiz', [$course, $quiz])).'">العودة للاختبار</a>')])
@endsection
