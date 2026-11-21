@extends('site.dashboard.layout')

@section('title', 'نتائج الاختبارات')

@section('dash')
    @if ($attempts->isEmpty())
        <div class="empty">لم تؤدِّ أي اختبار بعد.</div>
    @else
        <table class="table">
            <tr><th>الاختبار</th><th>الدورة</th><th>الدرجة</th><th>النتيجة</th><th>التاريخ</th><th></th></tr>
            @foreach ($attempts as $attempt)
                <tr>
                    <td><b>{{ $attempt->quiz?->title }}</b></td>
                    <td>{{ $attempt->course?->title }}</td>
                    <td>{{ $attempt->score }}%</td>
                    <td><span class="pill {{ $attempt->passed ? 'active' : 'cancelled' }}">{{ $attempt->passed ? 'ناجح' : 'لم يجتز' }}</span></td>
                    <td>{{ $attempt->created_at->format('Y-m-d H:i') }}</td>
                    <td>@if ($attempt->course)<a href="{{ route('learn.attempt', [$attempt->course, $attempt]) }}">التفاصيل</a>@endif</td>
                </tr>
            @endforeach
        </table>
        {{ $attempts->links() }}
    @endif
@endsection
