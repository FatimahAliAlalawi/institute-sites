@extends('layouts.admin')

@section('title', 'تحضير: '.$program->title)

@section('actions')
    <a href="{{ route('admin.course-attendance.index') }}" class="btn"><i class="fa-regular fa-calendar"></i> التقويم</a>
    <a href="{{ route('admin.course-attendance.ics', ['program' => $program->id]) }}" class="btn"><i class="fa-regular fa-calendar-plus"></i> أضف للتقويم</a>
    @if (isset($currentAdmin) && $currentAdmin->allows('programs'))
        <a href="{{ route('admin.programs.ops', $program) }}" class="btn"><i class="fa-solid fa-list-check"></i> تشغيل الدورة</a>
    @endif
@endsection

@section('content')
    <form class="card" method="get" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        <div class="field" style="margin:0">
            <label for="date">يوم التحضير</label>
            <input type="date" id="date" name="date" value="{{ $date }}" onchange="this.form.submit()">
        </div>
        @if ($dates->isNotEmpty())
            <div class="sub" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">أيام الدورة:
                @foreach ($dates as $d)
                    @php($done = in_array($d, $recordedDates, true))
                    @php($cls = $d === $date ? 'b-new' : ($done ? 'b-on' : ($d < now()->toDateString() ? 'b-rejected' : 'b-off')))
                    <a href="{{ route('admin.course-attendance.show', [$program, 'date' => $d]) }}" class="badge {{ $cls }}" style="text-decoration:none" title="{{ $done ? 'تم التحضير' : ($d < now()->toDateString() ? 'فات بدون تحضير' : 'قادم') }}">{{ $done ? '✓ ' : '' }}{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('D j/n') }}</a>
                @endforeach
            </div>
        @endif
        @if ($sessions && ! in_array($date, $sessions, true))
            <div class="alert alert-error" style="margin:0">هذا اليوم ليس من أيام الدورة في التقويم.</div>
        @endif
    </form>

    @php($atRisk = $trainees->filter(fn ($t) => $summary[$t->id]['cert'] !== 'ok'))
    @if ($atRisk->isNotEmpty())
        @php($maxPct = $summary[$atRisk->first()->id]['max_pct'])
        <div class="card" style="border-color:#f0b4ae;background:#fff6f5">
            <h2 style="color:#b42318"><i class="fa-solid fa-triangle-exclamation"></i> تنبيه الغياب (الحد المسموح {{ $maxPct }}% من أيام الدورة)</h2>
            <ul style="margin:6px 0 0;padding-inline-start:1.2em">
                @foreach ($atRisk as $t)
                    @php($s = $summary[$t->id])
                    <li><b>{{ $t->name }}</b>: غاب {{ $s['missed'] }} من {{ $s['total'] }} يومًا ({{ $s['absence_pct'] }}%) —
                        @if ($s['cert'] === 'over')<span style="color:#b42318;font-weight:700">تجاوز الحد، لا يستحق الشهادة</span>@elseif ($s['remaining'] === 0)<span style="color:#b54708;font-weight:700">وصل للحد: أي غياب آخر يُسقط الشهادة</span>@else<span style="color:#b54708;font-weight:700">باقي له يوم غياب واحد فقط</span>@endif
                    </li>
                @endforeach
            </ul>
            <p class="sub" style="margin:6px 0 0">بلّغ المتدرب برسالة «تقرير الحضور» من «تشغيل الدورة»{{ \App\Models\ProgramAttendance::excusedCounts() ? '. الغياب بعذر محسوب ضمن النسبة' : '. الغياب بعذر غير محسوب ضمن النسبة' }} (يُغيَّر من الإعدادات ← الحضور والشهادة).</p>
        </div>
    @endif

    <form class="card" method="post" action="{{ route('admin.course-attendance.store', $program) }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <h2>{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l j F Y') }}</h2>
        @if ($trainees->isEmpty())
            <div class="empty">لا يوجد متدربون مقبولون في هذه الدورة.</div>
        @else
            <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
                <button type="button" class="btn btn-sm" onclick="document.querySelectorAll('input[value=present]').forEach(i=>i.checked=true)"><i class="fa-solid fa-check-double"></i> الكل حاضر</button>
            </div>
            <div class="table-wrap">
                <table>
                    <tr><th>المتدرب</th>@foreach (\App\Models\ProgramAttendance::STATUSES as $label)<th style="text-align:center">{{ $label }}</th>@endforeach<th>ملاحظة</th><th>الإجمالي</th></tr>
                    @foreach ($trainees as $t)
                        @php($cur = $records[$t->id]->status ?? null)
                        @php($s = $summary[$t->id])
                        <tr>
                            <td><b>{{ $t->name }}</b><div class="sub" dir="ltr" style="text-align:right">{{ $t->phone }}</div></td>
                            @foreach (array_keys(\App\Models\ProgramAttendance::STATUSES) as $st)
                                <td style="text-align:center"><input type="radio" name="status[{{ $t->id }}]" value="{{ $st }}" @checked($cur === $st) style="width:20px;height:20px"></td>
                            @endforeach
                            <td><input type="text" name="note[{{ $t->id }}]" value="{{ $records[$t->id]->note ?? '' }}" placeholder="اختياري" style="min-width:120px"></td>
                            <td class="sub" style="white-space:nowrap">حضر {{ $s['attended'] }} من {{ $s['days'] }}@if ($s['missed'])<div style="color:#b42318">غياب {{ $s['missed'] }} من {{ $s['total'] }} ({{ $s['absence_pct'] }}%)</div>@endif
                                @if ($s['cert'] !== 'ok')@php([$cl, $cc] = \App\Models\ProgramAttendance::CERT_LABELS[$s['cert']])<span class="badge {{ $cc }}">{{ $cl }}</span>@endif</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @if ($records->isNotEmpty())<p class="sub">آخر تسجيل: {{ $records->first()->recorded_by }} — {{ $records->max('updated_at')?->format('H:i') }}</p>@endif
            <div class="form-foot sticky-foot"><button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> حفظ التحضير</button></div>
        @endif
    </form>
@endsection
