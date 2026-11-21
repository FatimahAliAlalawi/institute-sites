@extends('layouts.admin')

@section('title', 'نتائج الاختبارات')

@section('content')
    <div class="card">
        <form class="filters" method="get">
            <div class="field">
                <label>الدورة</label>
                <select name="course">
                    <option value="">كل الدورات</option>
                    @foreach ($courses as $c)<option value="{{ $c->id }}" @selected((string) request('course') === (string) $c->id)>{{ $c->title }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label>النتيجة</label>
                <select name="result">
                    <option value="">الكل</option>
                    <option value="passed" @selected(request('result') === 'passed')>ناجح</option>
                    <option value="failed" @selected(request('result') === 'failed')>لم يجتز</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> تصفية</button>
        </form>
        @if ($attempts->isEmpty())
            <div class="empty">لا توجد نتائج.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الطالب</th><th>الاختبار</th><th>الدورة</th><th>الدرجة</th><th>النتيجة</th><th>التاريخ</th></tr>
                    @foreach ($attempts as $a)
                        <tr>
                            <td>@if ($a->user)<a href="{{ route('admin.students.show', $a->user) }}">{{ $a->user->name }}</a>@endif</td>
                            <td>{{ $a->quiz?->title }}</td>
                            <td class="sub">{{ $a->course?->title }}</td>
                            <td><b>{{ $a->score }}%</b> <span class="sub">({{ $a->earned_points }}/{{ $a->total_points }})</span></td>
                            <td><span class="badge {{ $a->passed ? 'b-accepted' : 'b-rejected' }}">{{ $a->passed ? 'ناجح' : 'لم يجتز' }}</span></td>
                            <td class="sub">{{ $a->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $attempts->links() }}
        @endif
    </div>
@endsection
