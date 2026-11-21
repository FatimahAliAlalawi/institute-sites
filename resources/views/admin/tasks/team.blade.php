@extends('layouts.admin')

@section('title', 'متابعة الفريق')

@section('content')
    <div class="card">
        <h2>وين وصلت العملية عند كل موظف</h2>
        @if ($team->isEmpty())
            <div class="empty">لا توجد مهام مسندة لأي موظف. اكتب أسماء الموظفين (كما في حساباتهم) في خانة «المسؤول» داخل «تشغيل الدورة».</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الموظف</th><th>مفتوحة</th><th>متأخرة</th><th>اليوم</th><th>تمت</th><th>نسبة الإنجاز</th></tr>
                    @foreach ($team as $row)
                        @php($t = $row['tasks'])
                        @php($open = $t->where('done', false))
                        @php($pct = $t->count() ? (int) round($t->where('done', true)->count() / $t->count() * 100) : 0)
                        <tr>
                            <td><a href="#emp{{ $row['admin']->id }}"><b>{{ $row['admin']->name }}</b></a><div class="sub">{{ $row['admin']->role_label }}</div></td>
                            <td>{{ $open->count() }}</td>
                            <td>@if ($n = $open->where('overdue', true)->count())<span class="badge b-rejected">{{ $n }}</span>@else 0 @endif</td>
                            <td>{{ $open->where('today', true)->count() }}</td>
                            <td>{{ $t->where('done', true)->count() }}</td>
                            <td style="min-width:140px">
                                <span style="display:block;height:8px;background:#eceaf3;border-radius:999px;overflow:hidden"><span style="display:block;height:100%;width:{{ $pct }}%;background:{{ $pct === 100 ? '#16794a' : '#32255D' }}"></span></span>
                                <span class="sub">{{ $pct }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    @if ($programs->isNotEmpty())
        <div class="card">
            <h2>الدورات: وين وصلت كل دورة</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>الدورة</th><th>البدء</th><th>المتدربون</th>
                        @foreach (\App\Models\Program::OPS_STEPS as $key => [$title])
                            @continue($key === 'register')
                            <th style="text-align:center;font-size:11px">{{ $title }}</th>
                        @endforeach
                    </tr>
                    @foreach ($programs as $p)
                        @php($n = $p->seatRegistrations->count())
                        <tr>
                            <td><a href="{{ route('admin.programs.ops', $p) }}"><b>{{ $p->title }}</b></a></td>
                            <td class="sub" style="white-space:nowrap">{{ $p->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $n }}</td>
                            @foreach (\App\Models\Program::OPS_STEPS as $key => [$title, , $kind])
                                @continue($key === 'register')
                                @php($due = $p->opsDue($key))
                                @if ($kind === 'course')
                                    @php($ok = (bool) $p->opsCourseDone($key))
                                @else
                                    @php($done = $p->seatRegistrations->filter(fn ($t) => $t->stepDone($key))->count())
                                    @php($ok = $n > 0 && $done === $n)
                                @endif
                                @php($late = ! $ok && $due && $due->lt(now()->startOfDay()) && ($kind === 'course' || $n))
                                <td style="text-align:center" title="{{ $p->opsAssignees($key) }}">
                                    @if ($ok)<span style="color:#16794a">✔</span>
                                    @elseif ($kind === 'trainee' && $n)<span @if ($late) style="color:#b42318;font-weight:700" @endif>{{ $done }}/{{ $n }}</span>
                                    @elseif ($kind === 'trainee')<span class="sub">—</span>
                                    @else<span @if ($late) style="color:#b42318;font-weight:700" @else class="sub" @endif>{{ $late ? 'متأخرة' : '…' }}</span>@endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
            <p class="sub">مرّر المؤشر على أي خانة لترى المسؤول عنها.</p>
        </div>
    @endif

    @foreach ($team as $row)
        <div class="card" id="emp{{ $row['admin']->id }}">
            <h2>{{ $row['admin']->name }}</h2>
            @include('admin.tasks._list', ['tasks' => $row['tasks']])
        </div>
    @endforeach
@endsection
