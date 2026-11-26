@extends('layouts.admin')

@section('title', 'الاختبار القبلي والبعدي: '.$program->title)

@section('actions')
    <a href="{{ route('admin.course-attendance.show', $program) }}" class="btn"><i class="fa-solid fa-clipboard-check"></i> التحضير</a>
    @if ($currentAdmin->allows('programs'))
        <a href="{{ route('admin.programs.ops', $program) }}" class="btn"><i class="fa-solid fa-list-check"></i> تشغيل الدورة</a>
    @endif
    <a href="{{ route('admin.course-attendance.tests.export', $program) }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير Excel</a>
    <a href="{{ route('admin.course-attendance.files', [$program, 'kind' => 'pre_report']) }}" class="btn"><i class="fa-solid fa-folder-open"></i> رفع تقرير الاختبار</a>
@endsection

@php
    $kinds = \App\Models\ProgramTestResult::KINDS;
    $total = $trainees->count();
    $rows = array_values(old('q', $questions->map(fn ($q) => ['question' => $q->question, 'options' => $q->options, 'correct' => $q->correct])->all()));
    $rows = $rows ?: [['question' => '', 'options' => [], 'correct' => null]];
@endphp

@section('content')
    <div class="stats">
        <div class="stat"><b>{{ $questions->count() }}</b><span>سؤال</span></div>
        <div class="stat"><b>{{ $summary['pre_count'] }} / {{ $total }}</b><span>أدّوا القبلي — المتوسط {{ $summary['pre_avg'] !== null ? $summary['pre_avg'].'%' : '—' }}</span></div>
        <div class="stat"><b>{{ $summary['post_count'] }} / {{ $total }}</b><span>أدّوا البعدي — المتوسط {{ $summary['post_avg'] !== null ? $summary['post_avg'].'%' : '—' }}</span></div>
        <div class="stat"><b style="color:{{ ($summary['gain'] ?? 0) < 0 ? '#b42318' : '#16794a' }}">{{ $summary['gain'] !== null ? ($summary['gain'] > 0 ? '+' : '').$summary['gain'] : '—' }}</b><span>التحسّن بالنقاط ({{ $summary['improved'] }} من {{ $summary['both'] }} تحسّنوا)</span></div>
    </div>

    <div class="card">
        <h2>فتح وإغلاق الاختبار</h2>
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            @foreach ($kinds as $kind => $label)
                @php($isOpen = (bool) $program->{$kind.'_test_open'})
                <form method="post" action="{{ route('admin.course-attendance.tests.toggle', $program) }}" class="actions">
                    @csrf
                    <input type="hidden" name="kind" value="{{ $kind }}">
                    <b>{{ $label }}</b> <span class="badge {{ $isOpen ? 'b-on' : 'b-off' }}">{{ $isOpen ? 'مفتوح' : 'مغلق' }}</span>
                    <button name="open" value="{{ $isOpen ? 0 : 1 }}" class="btn btn-sm {{ $isOpen ? '' : 'btn-primary' }}">{{ $isOpen ? 'إغلاق' : 'فتح' }}</button>
                </form>
            @endforeach
        </div>
        <p class="sub" style="margin:8px 0 0">افتح القبلي قبل بداية الدورة وأغلقه بعد بدايتها، وافتح البعدي في آخر يوم. كل متدرب له رابط خاص ويجيب مرة واحدة.</p>
    </div>

    <div class="card">
        <h2>المتدربون والنتائج</h2>
        @if ($trainees->isEmpty())
            <div class="empty">لا يوجد متدربون مقبولون في هذه الدورة بعد.</div>
        @else
            <form method="post" action="{{ route('admin.course-attendance.tests.scores', $program) }}" id="scores">@csrf</form>
            <div class="table-wrap">
                <table>
                    <tr><th>المتدرب</th>@foreach ($kinds as $label)<th>{{ $label }}</th>@endforeach<th>الفرق</th></tr>
                    @foreach ($trainees as $t)
                        @php($mine = $byTrainee->get($t->id, collect()))
                        @php($pre = $mine->get('pre'))
                        @php($post = $mine->get('post'))
                        <tr>
                            <td><b>{{ $t->name }}</b></td>
                            @foreach ($kinds as $kind => $label)
                                @php($r = $mine->get($kind))
                                <td style="white-space:nowrap">
                                    @if ($r && $r->answers !== null)
                                        <b>{{ $r->score }}%</b> <span class="sub">({{ $r->earned }} من {{ $r->total }})</span>
                                        <form method="post" action="{{ route('admin.course-attendance.tests.reset', [$program, $r]) }}" data-confirm="حذف نتيجة {{ $t->name }} حتى يعيد الاختبار؟" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm" title="حذف النتيجة للإعادة"><i class="fa-solid fa-rotate-left"></i></button>
                                        </form>
                                    @else
                                        <input form="scores" type="number" min="0" max="100" name="score[{{ $t->id }}][{{ $kind }}]" value="{{ $r?->score }}" placeholder="ورقي %" aria-label="درجة {{ $label }} — {{ $t->name }}" style="width:95px;display:inline-block">
                                        @if ($program->testOpen($kind) && ! $r)
                                            @php($url = route('program-test', [$t->test_token, $kind]))
                                            <a class="btn btn-sm" target="_blank" rel="noopener" title="إرسال الرابط واتساب" href="{{ \App\Support\TraineeMessages::whatsappUrl($t, \App\Support\TraineeMessages::render($kind.'_test', $t, $program)) }}"><i class="fa-brands fa-whatsapp" style="color:#128C7E"></i></a>
                                            <button type="button" class="btn btn-sm copy" data-url="{{ $url }}" title="نسخ الرابط"><i class="fa-regular fa-copy"></i></button>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            <td>@if ($pre && $post)<b style="color:{{ $post->score < $pre->score ? '#b42318' : '#16794a' }}" dir="ltr">{{ $post->score > $pre->score ? '+' : '' }}{{ $post->score - $pre->score }}</b>@else — @endif</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <p class="sub">الاختبار الورقي: اكتب الدرجة من 100 واحفظ. زر واتساب يظهر لما يكون الاختبار مفتوحًا.</p>
            <button form="scores" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ الدرجات الورقية</button>
        @endif
    </div>

    <div class="card">
        <h2>الأسئلة (نفسها للقبلي والبعدي)</h2>
        @if ($locked)
            <div class="alert alert-error">الأسئلة مقفلة لأن المتدربين بدؤوا الإجابة، حتى تبقى المقارنة صحيحة.</div>
            <div class="table-wrap">
                <table>
                    @foreach ($questions as $q)
                        <tr><td><b>{{ $loop->iteration }}. {{ $q->question }}</b>
                            @foreach ($q->options as $i => $o)<div @if ($i === $q->correct) style="color:#16794a;font-weight:700" @else class="sub" @endif>{{ $i === $q->correct ? '✓' : '•' }} {{ $o }}</div>@endforeach
                        </td></tr>
                    @endforeach
                </table>
            </div>
        @else
            @if ($questions->isEmpty() && $others->isNotEmpty())
                <form method="post" action="{{ route('admin.course-attendance.tests.copy', $program) }}" class="filters" style="margin-bottom:14px">
                    @csrf
                    <div class="field"><label for="from">نسخ الأسئلة من دورة سابقة</label>
                        <select id="from" name="from">@foreach ($others as $o)<option value="{{ $o->id }}">{{ $o->title }}</option>@endforeach</select></div>
                    <button class="btn">نسخ</button>
                </form>
            @endif
            <form method="post" action="{{ route('admin.course-attendance.tests.questions', $program) }}">
                @csrf
                <div id="qs">
                    @foreach ($rows as $i => $row)
                        <div class="qrow" style="border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px">
                            <div class="field"><label>نص السؤال</label><textarea name="q[{{ $i }}][question]" rows="2">{{ $row['question'] ?? '' }}</textarea></div>
                            <div class="sub">اكتب الخيارات وحدّد الدائرة بجانب الإجابة الصحيحة (للصح والخطأ: خيارين فقط).</div>
                            @for ($o = 0; $o < max(4, count($row['options'] ?? [])); $o++)
                                <div style="display:flex;gap:8px;align-items:center;margin-top:6px">
                                    <input type="radio" name="q[{{ $i }}][correct]" value="{{ $o }}" aria-label="الإجابة الصحيحة" style="width:auto" @checked(isset($row['correct']) && (string) $row['correct'] === (string) $o)>
                                    <input type="text" name="q[{{ $i }}][options][{{ $o }}]" value="{{ $row['options'][$o] ?? '' }}" placeholder="الخيار {{ $o + 1 }}">
                                </div>
                            @endfor
                            <button type="button" class="btn btn-sm del" style="margin-top:8px"><i class="fa-regular fa-trash-can"></i> حذف السؤال</button>
                        </div>
                    @endforeach
                </div>
                <div class="actions">
                    <button type="button" class="btn" id="addq"><i class="fa-solid fa-plus"></i> سؤال جديد</button>
                    <button class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ الأسئلة</button>
                </div>
            </form>
        @endif
    </div>

    <script>
        document.querySelectorAll('.copy').forEach(function (b) {
            b.addEventListener('click', function () {
                var done = function () { b.innerHTML = '<i class="fa-solid fa-check"></i>'; };
                if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.url).then(done, function () { prompt('انسخ الرابط', b.dataset.url); }); }
                else { prompt('انسخ الرابط', b.dataset.url); }
            });
        });
        (function () {
            var box = document.getElementById('qs'), add = document.getElementById('addq');
            if (!box) return;
            var next = {{ count($rows) }};
            var blank = box.querySelector('.qrow').cloneNode(true);
            add.addEventListener('click', function () {
                var row = blank.cloneNode(true), i = next++;
                row.querySelectorAll('[name]').forEach(function (el) {
                    el.name = el.name.replace(/^q\[\d+\]/, 'q[' + i + ']');
                    if (el.type === 'radio') { el.checked = false; } else { el.value = ''; }
                });
                box.appendChild(row);
                row.querySelector('textarea').focus();
            });
            box.addEventListener('click', function (e) {
                var del = e.target.closest('.del');
                if (del) del.closest('.qrow').remove();
            });
        })();
    </script>
@endsection
