@extends('layouts.admin')

@section('title', 'التحضير — الدورات المسجلة')

@section('actions')
    @if ($course)
        <a href="{{ route('admin.attendance.index', ['course' => $course->id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 1]) }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير Excel</a>
    @endif
@endsection

@section('content')
    @if ($courses->isEmpty())
        <div class="card empty">
            لا توجد دورات مسجلة بنظام "درس يوميًا" بعد.<br>
            افتح أي دورة من <a href="{{ route('admin.courses.index') }}">الدورات</a> وفعّل خيار <b>"دورة مسجلة: تُفتح الدروس تدريجيًا"</b>.
        </div>
    @else
        <div class="card">
            <form class="filters" method="get">
                <div class="field">
                    <label>الدورة</label>
                    <select name="course">
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" @selected($course?->id === $c->id)>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>من</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
                <div class="field"><label>إلى</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> عرض</button>
                <a class="btn" href="{{ route('admin.attendance.index', ['course' => $course?->id, 'from' => $from->copy()->subDays(7)->format('Y-m-d'), 'to' => $to->copy()->subDays(7)->format('Y-m-d')]) }}">→ الأسبوع السابق</a>
                <a class="btn" href="{{ route('admin.attendance.index', ['course' => $course?->id, 'from' => $from->copy()->addDays(7)->format('Y-m-d'), 'to' => $to->copy()->addDays(7)->format('Y-m-d')]) }}">الأسبوع التالي ←</a>
            </form>
            <div class="sub" style="margin-bottom:12px">
                الحضور = إكمال المتدرب درسًا واحدًا على الأقل في ذلك اليوم. ·
                <span class="att present">✓</span> حاضر
                <span class="att absent">✗</span> غائب
                <span class="att today">…</span> اليوم (لم يحضر بعد)
                <span class="att off">—</span> ليس يوم دراسة / خارج فترة الاشتراك
            </div>

            @if ($rows->isEmpty())
                <div class="empty">لا يوجد متدربون مشتركون في هذه الدورة.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>المتدرب</th>
                            @foreach ($dates as $d)
                                <th style="text-align:center;{{ $d->isToday() ? 'color:var(--primary)' : '' }}">{{ \App\Support\DailyRelease::DAYS[$d->dayOfWeek] }}<br><span class="sub">{{ $d->format('m/d') }}</span></th>
                            @endforeach
                            <th>حضور</th><th>غياب</th><th>النسبة</th><th>التقدم</th>
                        </tr>
                        @foreach ($rows as $row)
                            @php $total = $row['present'] + $row['absent']; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.students.show', $row['user']) }}"><b>{{ $row['user']->name }}</b></a>
                                    <div class="sub">{{ \App\Support\DailyRelease::label($row['days']) }}</div>
                                </td>
                                @foreach ($row['records'] as $date => $status)
                                    <td style="text-align:center"><span class="att {{ $status }}" title="{{ \App\Http\Controllers\Admin\AttendanceController::LABELS[$status] }}">{{ ['present' => '✓', 'absent' => '✗', 'today' => '…'][$status] ?? '—' }}</span></td>
                                @endforeach
                                <td><b style="color:var(--success)">{{ $row['present'] }}</b></td>
                                <td><b style="color:var(--danger)">{{ $row['absent'] }}</b></td>
                                <td>{{ $total ? round($row['present'] * 100 / $total).'%' : '—' }}</td>
                                <td class="sub">{{ $row['progress'] }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    @endif
@endsection
