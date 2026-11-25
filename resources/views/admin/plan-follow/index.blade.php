@extends('layouts.admin')

@section('title', 'متابعة الخطة')

@section('actions')
    <a href="{{ route('admin.plan-follow.export', request()->query()) }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير Excel</a>
    <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> طباعة</button>
@endsection

@section('content')
    <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <a class="btn btn-sm" href="{{ route('admin.plan-follow.index', ['from' => $prev]) }}">الثلاثة أشهر السابقة</a>
        <b>من {{ $start->translatedFormat('j F Y') }} إلى {{ $end->translatedFormat('j F Y') }}</b>
        <a class="btn btn-sm" href="{{ route('admin.plan-follow.index', ['from' => $next]) }}">الثلاثة أشهر التالية</a>
    </div>

    <div class="stats">
        <div class="stat"><b>{{ $kpi['planned'] }}</b><span>دورة في الخطة</span></div>
        <div class="stat"><b>{{ $kpi['done'] }}</b><span>نُفّذت</span></div>
        <div class="stat"><b>{{ $kpi['rate'] !== null ? $kpi['rate'].'%' : '—' }}</b><span>نسبة التنفيذ (مما حان موعده)</span></div>
        <div class="stat"><b>{{ $kpi['running'] }} / {{ $kpi['upcoming'] }}</b><span>جارية / قادمة</span></div>
        <div class="stat"><b>{{ $kpi['postponed'] }} / {{ $kpi['cancelled'] }}</b><span>مؤجلة / ملغاة</span></div>
        <div class="stat"><b @if ($kpi['attention']) style="color:#b42318" @endif>{{ $kpi['attention'] }}</b><span>تحتاج إجراء</span></div>
    </div>

    @forelse ($months as $month => $rows)
        <div class="card">
            <h2>{{ $month === '9999-99' ? 'بلا تاريخ' : \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $month.'-01')->translatedFormat('F Y') }} <span class="sub">({{ $rows->count() }})</span></h2>
            <div class="table-wrap">
                <table>
                    <tr><th>الدورة</th><th>المخطط ← الحالي</th><th>الحالة</th><th>المسجلون</th><th>الحضور</th><th>قبلي ← بعدي</th><th>المطلوب الآن</th><th>تأجيل أو إلغاء</th></tr>
                    @foreach ($rows as $r)
                        @php($p = $r['program'])
                        @php([$label, $badge] = \App\Http\Controllers\Admin\PlanFollowController::STATES[$r['state']])
                        <tr>
                            <td><a href="{{ $currentAdmin->allows('programs') ? route('admin.programs.ops', $p) : route('programs.show', $p) }}"><b>{{ $p->title }}</b></a><div class="sub">{{ $p->trainer_name ?? '' }}</div></td>
                            <td class="sub" style="white-space:nowrap">{{ $p->plan_baseline_date?->format('Y-m-d') ?? '—' }}
                                @if ($r['shift'])<div style="color:#b54708">← {{ $p->start_date->format('Y-m-d') }}<br>{{ $r['shift'] > 0 ? 'تأخرت' : 'تقدّمت' }} {{ abs($r['shift']) }} يوم</div>@endif
                            </td>
                            <td><span class="badge {{ $badge }}">{{ $label }}</span>@if ($p->plan_exec_note)<div class="sub">{{ $p->plan_exec_note }}</div>@endif</td>
                            <td style="white-space:nowrap">{{ $r['registered'] }}{{ $p->seats ? ' / '.$p->seats : '' }}</td>
                            <td>{{ $r['attendance'] !== null ? $r['attendance'].'%' : '—' }}</td>
                            <td style="white-space:nowrap">
                                {{ $r['test']['pre_avg'] !== null ? $r['test']['pre_avg'].'%' : '—' }} ← {{ $r['test']['post_avg'] !== null ? $r['test']['post_avg'].'%' : '—' }}
                                @if ($r['test']['gain'] !== null)<div style="color:{{ $r['test']['gain'] < 0 ? '#b42318' : '#16794a' }}"><span dir="ltr">{{ $r['test']['gain'] > 0 ? '+' : '' }}{{ $r['test']['gain'] }}</span> نقطة</div>@endif
                                @if ($currentAdmin->allows('course_attendance'))<a class="sub" href="{{ route('admin.course-attendance.tests', $p) }}">الاختبارات</a>@endif
                            </td>
                            <td>@forelse ($r['alerts'] as $alert)<div class="sub" style="color:#b42318">• {{ $alert }}</div>@empty<span class="badge b-on">لا شيء</span>@endforelse</td>
                            <td>
                                <form method="post" action="{{ route('admin.plan-follow.update', $p) }}">
                                    @csrf
                                    <select name="plan_exec" aria-label="حالة التنفيذ" style="min-width:120px">
                                        <option value="">حسب الموعد</option>
                                        @foreach (\App\Models\Program::PLAN_EXEC as $k => $l)<option value="{{ $k }}" @selected($p->plan_exec === $k)>{{ $l }}</option>@endforeach
                                    </select>
                                    <input type="text" name="plan_exec_note" value="{{ $p->plan_exec_note }}" placeholder="السبب" aria-label="السبب" maxlength="500" style="margin-top:4px">
                                    <button class="btn btn-sm" style="margin-top:4px">حفظ</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @empty
        <div class="card"><div class="empty">لا توجد دورات معتمدة في الخطة لهذه الفترة.</div></div>
    @endforelse

    <p class="sub">«الموعد المخطط» يُحفظ عند أول اعتماد للخطة ولا يتغير بتعديل التاريخ بعده. «نُفّذت» = انتهى آخر يوم وفيها تحضير مسجل. الحد الأدنى لعقد الدورة: {{ $min }} متدربين (من الإعدادات). إذا لم تنعقد الدورة يُسترد المبلغ حسب سياسة المعهد.</p>
@endsection
