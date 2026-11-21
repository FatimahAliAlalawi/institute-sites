@extends('site.dashboard.layout')

@section('title', 'شهاداتي')

@section('dash')
    @if ($certificates->isEmpty())
        <div class="empty">لا توجد شهادات بعد — أكمل جميع دروس واختبارات أي دورة لتحصل على شهادتها.</div>
    @else
        <table class="table">
            <tr><th>الدورة</th><th>تاريخ الإصدار</th><th>رمز التحقق</th><th></th></tr>
            @foreach ($certificates as $certificate)
                <tr>
                    <td><b>{{ $certificate->course?->title }}</b></td>
                    <td>{{ $certificate->issued_at->format('Y-m-d') }}</td>
                    <td dir="ltr" style="text-align:right">{{ $certificate->code }}</td>
                    <td><a class="btn btn-outline" style="padding:6px 16px" href="{{ route('certificates.show', $certificate->code) }}" target="_blank"><i class="fa-solid fa-award"></i> عرض وطباعة</a></td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
