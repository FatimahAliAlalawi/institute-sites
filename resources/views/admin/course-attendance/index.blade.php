@extends('layouts.admin')

@section('title', $trainerOnly ? 'دوراتي والتحضير' : 'تحضير الدورات الحضورية')

@section('actions')
    <a href="{{ route('admin.course-attendance.ics') }}" class="btn"><i class="fa-regular fa-calendar-plus"></i> أضف دوراتي للتقويم</a>
@endsection

@php
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $todayStr = now()->toDateString();
    $lead = $month->dayOfWeek; // الأحد = 0 أول عمود
    $daysInMonth = $month->daysInMonth;
@endphp

@section('content')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
            <a class="btn btn-sm" href="{{ route('admin.course-attendance.index', ['month' => $prev]) }}"><i class="fa-solid fa-arrow-right"></i> الشهر السابق</a>
            <h2 style="margin:0">{{ $month->translatedFormat('F Y') }}</h2>
            <a class="btn btn-sm" href="{{ route('admin.course-attendance.index', ['month' => $next]) }}">الشهر التالي <i class="fa-solid fa-arrow-left"></i></a>
        </div>
        <div class="cal">
            @foreach (\App\Models\Program::WEEKDAYS as $dn)<div class="cal-h">{{ $dn }}</div>@endforeach
            @for ($i = 0; $i < $lead; $i++)<div class="cal-d empty"></div>@endfor
            @for ($d = 1; $d <= $daysInMonth; $d++)
                @php($date = $month->copy()->day($d)->toDateString())
                <div @class(['cal-d', 'today' => $date === $todayStr])>
                    <span class="n">{{ $d }}</span>
                    @foreach ($days[$date] ?? [] as $item)
                        @php($state = $item['done'] ? 'done' : ($date < $todayStr ? 'missed' : ($date === $todayStr ? 'now' : 'soon')))
                        <a href="{{ route('admin.course-attendance.show', [$item['program'], 'date' => $date]) }}" class="ev {{ $state }}" title="{{ $item['program']->title }}">
                            {{ $item['done'] ? '✓ ' : '' }}{{ \Illuminate\Support\Str::limit($item['program']->title, 22) }}
                        </a>
                    @endforeach
                </div>
            @endfor
        </div>
        <div class="sub" style="margin-top:10px;display:flex;gap:14px;flex-wrap:wrap">
            <span><span class="ev done">✓</span> تم التحضير</span>
            <span><span class="ev now">اليوم</span> مطلوب اليوم</span>
            <span><span class="ev missed">!</span> فات بدون تحضير</span>
            <span><span class="ev soon">قادم</span></span>
        </div>
    </div>

    <div class="card">
        <h2>{{ $trainerOnly ? 'دوراتي' : 'الدورات' }}</h2>
        @if ($programs->isEmpty())
            <div class="empty">{{ $trainerOnly ? 'لا توجد دورات مربوطة بحسابك بعد. تواصل مع الإدارة.' : 'لا توجد دورات فيها متدربون أو أيام في التقويم بعد.' }}</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الدورة</th><th>الأيام</th><th>الوقت</th><th>المدرب</th><th>المتدربون</th><th>تحضير اليوم</th><th></th></tr>
                    @foreach ($programs as $p)
                        <tr>
                            <td><b>{{ $p->title }}</b><div class="sub">{{ $p->session_dates ? count($p->session_dates).' أيام: '.\Illuminate\Support\Carbon::parse($p->session_dates[0])->format('m/d').' – '.\Illuminate\Support\Carbon::parse(\Illuminate\Support\Arr::last($p->session_dates))->format('m/d') : ($p->start_date?->format('Y-m-d') ?? '—') }}</div></td>
                            <td class="sub">{{ $p->schedule_days ?? '—' }}</td>
                            <td class="sub">{{ $p->schedule_time ?? '—' }}</td>
                            <td class="sub">{{ $p->trainer_name ?? '—' }}</td>
                            <td>{{ $p->seat_registrations_count }}</td>
                            <td>@if (in_array($p->id, $today))<span class="badge b-on">تم</span>@elseif (in_array($todayStr, $p->session_dates, true))<span class="badge b-rejected">مطلوب</span>@else<span class="badge b-off">—</span>@endif</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.course-attendance.show', $p) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-clipboard-check"></i> تحضير</a>
                                    <a href="{{ route('admin.course-attendance.ics', ['program' => $p->id]) }}" class="btn btn-sm" title="أضف للتقويم"><i class="fa-regular fa-calendar-plus"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <style>
        .cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; }
        .cal-h { text-align: center; font-weight: 700; font-size: 12px; color: #6b6880; padding: 4px 0; }
        .cal-d { min-height: 78px; border: 1px solid #eceaf3; border-radius: 8px; padding: 4px; display: flex; flex-direction: column; gap: 3px; background: #fff; }
        .cal-d.empty { background: transparent; border: 0; }
        .cal-d.today { border-color: #1E99D1; box-shadow: inset 0 0 0 1px #1E99D1; }
        .cal-d .n { font-size: 12px; color: #6b6880; }
        .ev { display: inline-block; font-size: 11px; line-height: 1.4; border-radius: 6px; padding: 2px 5px; text-decoration: none; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .ev.done { background: #e6f6ee; color: #16794a; }
        .ev.now { background: #fff4e5; color: #b54708; font-weight: 700; }
        .ev.missed { background: #fdecec; color: #b42318; }
        .ev.soon { background: #ece8f7; color: #32255D; }
        @media (max-width: 700px) { .cal-d { min-height: 54px; } .ev { font-size: 10px; } }
    </style>
@endsection
