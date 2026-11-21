@extends('site.learn.player')

@section('title', $quiz->title.' — '.$course->title)

@section('player')
    <h1 style="font-size:26px;color:var(--primary)"><i class="fa-regular fa-circle-question"></i> {{ $quiz->title }}</h1>

    @if ($locked)
        <div class="result-box" style="padding:40px">
            <div style="font-size:54px;color:var(--accent)"><i class="fa-solid fa-lock"></i></div>
            <h2>الاختبار يُفتح بعد إكمال الدروس التي قبله</h2>
            <p class="muted" style="margin:0">أكمل الدروس السابقة حسب جدول الدورة ثم ارجع للاختبار.</p>
        </div>
        @include('site.learn.nav', ['slot' => new \Illuminate\Support\HtmlString('')])
    @elseif (! $started || $quiz->questions->isEmpty() || $attemptsLeft === 0)
        @if ($quiz->description)<p class="prose">{{ $quiz->description }}</p>@endif
        <div class="quiz-info">
            <div><b>{{ $quiz->questions->count() }}</b><small>سؤال</small></div>
            <div><b>{{ $quiz->pass_mark }}%</b><small>درجة النجاح</small></div>
            <div><b>{{ $quiz->time_limit ? $quiz->time_limit.' د' : 'مفتوح' }}</b><small>الوقت</small></div>
            <div><b>{{ $attemptsLeft ?? '∞' }}</b><small>محاولات متبقية</small></div>
        </div>

        @if ($attempts->isNotEmpty())
            <table class="table" style="margin-bottom:20px">
                <tr><th>المحاولة</th><th>الدرجة</th><th>النتيجة</th><th>التاريخ</th><th></th></tr>
                @foreach ($attempts as $a)
                    <tr>
                        <td>{{ $attempts->count() - $loop->index }}</td>
                        <td>{{ $a->score }}%</td>
                        <td><span class="pill {{ $a->passed ? 'active' : 'cancelled' }}">{{ $a->passed ? 'ناجح' : 'لم يجتز' }}</span></td>
                        <td>{{ $a->created_at->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('learn.attempt', [$course, $a]) }}">التفاصيل</a></td>
                    </tr>
                @endforeach
            </table>
        @endif

        @php
            $slot = '';
            if ($quiz->questions->isEmpty()) {
                $slot = '<span class="muted">لم تُضف أسئلة لهذا الاختبار بعد.</span>';
            } elseif ($attemptsLeft === 0) {
                $slot = '<span class="pill cancelled" style="padding:8px 18px">استنفدت عدد المحاولات</span>';
            } else {
                $slot = '<a class="btn btn-primary" href="'.e(route('learn.quiz', [$course, $quiz, 'start' => 1])).'"><i class="fa-solid fa-play"></i> '.($attempts->isEmpty() ? 'ابدأ الاختبار' : 'محاولة جديدة').'</a>';
            }
        @endphp
        @include('site.learn.nav', ['slot' => new \Illuminate\Support\HtmlString($slot)])
    @else
        @if ($quiz->time_limit)
            <div class="timer"><i class="fa-regular fa-clock"></i> <span id="timer" data-seconds="{{ $quiz->time_limit * 60 }}"></span></div>
        @endif
        <form method="post" action="{{ route('learn.quiz.submit', [$course, $quiz]) }}" id="quizForm">
            @csrf
            @foreach ($quiz->questions as $q)
                <div class="question">
                    <h4><span class="num">{{ $loop->iteration }}.</span> {{ $q->question }}
                        @if ($q->type === 'multiple')<small class="muted">(اختر كل الإجابات الصحيحة)</small>@endif
                    </h4>
                    @foreach ($q->options as $i => $option)
                        <label class="option">
                            <input type="{{ $q->type === 'multiple' ? 'checkbox' : 'radio' }}" name="answers[{{ $q->id }}][]" value="{{ $i }}">
                            {{ $option['text'] }}
                        </label>
                    @endforeach
                </div>
            @endforeach
            <button class="btn btn-primary" type="submit" onclick="return confirm('تسليم الاختبار؟')"><i class="fa-solid fa-paper-plane"></i> تسليم الإجابات</button>
        </form>
        @if ($quiz->time_limit)
            @push('scripts')
                <script>
                    (function () {
                        var el = document.getElementById('timer'), left = +el.dataset.seconds, form = document.getElementById('quizForm');
                        function tick() {
                            var m = Math.floor(left / 60), s = left % 60;
                            el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                            if (left-- <= 0) { form.submit(); return; }
                            setTimeout(tick, 1000);
                        }
                        tick();
                    })();
                </script>
            @endpush
        @endif
    @endif
@endsection
