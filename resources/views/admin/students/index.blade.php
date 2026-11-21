@extends('layouts.admin')

@section('title', 'الطلاب')

@section('actions')
    <a href="{{ route('admin.students.export') }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير Excel</a>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> إضافة طالب</a>
@endsection

@section('content')
    <div class="card">
        <form class="filters" method="get">
            <div class="field"><label>بحث</label><input type="search" name="q" value="{{ request('q') }}" placeholder="الاسم أو الهوية أو البريد أو الجوال"></div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> بحث</button>
        </form>

        @if ($students->isEmpty())
            <div class="empty">لا يوجد طلاب.</div>
        @else
            <form method="post" action="{{ route('admin.students.bulk-days') }}" id="bulkDays">
            @csrf
            <div class="table-wrap">
                <table>
                    <tr><th><input type="checkbox" onclick="document.querySelectorAll('.pick').forEach(c => c.checked = this.checked)" title="تحديد الكل"></th><th>الطالب</th><th>رقم الهوية</th><th>أيام الدراسة</th><th>الجوال</th><th>الدورات</th><th>الشهادات</th><th>التسجيل</th><th>آخر دخول</th><th></th></tr>
                    @foreach ($students as $s)
                        <tr>
                            <td><input type="checkbox" class="pick" name="ids[]" value="{{ $s->id }}"></td>
                            <td><a href="{{ route('admin.students.show', $s) }}"><b>{{ $s->name }}</b></a> @unless ($s->is_active)<span class="badge b-rejected">موقوف</span>@endunless<div class="sub">{{ $s->email }}</div></td>
                            <td dir="ltr" style="text-align:right">{{ $s->national_id ?: '—' }}</td>
                            <td class="sub">{{ $s->study_days ? \App\Support\DailyRelease::label(\App\Support\DailyRelease::parse($s->study_days)) : 'الإعداد العام' }}</td>
                            <td dir="ltr" style="text-align:right">{{ $s->phone }}</td>
                            <td>{{ $s->courses_count }}</td>
                            <td>{{ $s->certificates_count }}</td>
                            <td class="sub">{{ $s->created_at->format('Y-m-d') }}</td>
                            <td class="sub">{{ $s->last_login_at?->diffForHumans() ?? '—' }}</td>
                            <td><a href="{{ route('admin.students.show', $s) }}" class="btn btn-sm">الملف</a></td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="card" style="background:#faf9fd;margin:16px 0 0">
                <b><i class="fa-solid fa-calendar-days"></i> أيام الدراسة للمتدربين المحددين في الجدول:</b>
                <div class="checks" style="margin:10px 0">
                    <label><input type="radio" name="days_mode" value="global" checked> الإعداد العام ({{ \App\Support\DailyRelease::label(\App\Support\DailyRelease::globalDays()) }})</label>
                    <label><input type="radio" name="days_mode" value="custom"> أيام خاصة:</label>
                    @foreach (\App\Support\DailyRelease::DAYS as $num => $dayName)
                        <label style="font-weight:500"><input type="checkbox" name="study_days[]" value="{{ $num }}"> {{ $dayName }}</label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-sm" type="submit">تطبيق على المحددين</button>
            </div>
            </form>
            {{ $students->links() }}
        @endif
    </div>
@endsection
