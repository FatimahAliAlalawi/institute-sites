@extends('layouts.admin')

@section('title', 'تحضير الدورات الحضورية')

@section('content')
    <div class="card">
        <p class="sub" style="margin-top:0">اختر الدورة لتسجيل حضور اليوم. تظهر هنا الدورات اللي فيها متدربون مقبولون.</p>
        @if ($programs->isEmpty())
            <div class="empty">لا توجد دورات فيها متدربون مقبولون بعد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الدورة</th><th>تاريخ البدء</th><th>الأيام والوقت</th><th>المدرب</th><th>المتدربون</th><th>تحضير اليوم</th><th></th></tr>
                    @foreach ($programs as $p)
                        <tr>
                            <td><b>{{ $p->title }}</b></td>
                            <td class="sub" style="white-space:nowrap">{{ $p->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="sub">{{ $p->schedule_days }}{{ $p->schedule_days && $p->schedule_time ? ' | ' : '' }}{{ $p->schedule_time }}</td>
                            <td class="sub">{{ $p->trainer_name ?? '—' }}</td>
                            <td>{{ $p->seat_registrations_count }}</td>
                            <td>@if (in_array($p->id, $today))<span class="badge b-on">تم</span>@else<span class="badge b-off">لم يُسجّل</span>@endif</td>
                            <td><a href="{{ route('admin.course-attendance.show', $p) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-clipboard-check"></i> تحضير</a></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
