@extends('layouts.admin')

@section('title', 'الشهادات')

@section('content')
    <div class="card">
        <form class="filters" method="get">
            <div class="field"><label>بحث</label><input type="search" name="q" value="{{ request('q') }}" placeholder="رمز الشهادة أو اسم الطالب"></div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> بحث</button>
        </form>
        @if ($certificates->isEmpty())
            <div class="empty">لا توجد شهادات بعد — تُصدر تلقائيًا عند إتمام الطالب للدورة.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الطالب</th><th>الدورة</th><th>الرمز</th><th>تاريخ الإصدار</th><th></th></tr>
                    @foreach ($certificates as $c)
                        <tr>
                            <td>@if ($c->user)<a href="{{ route('admin.students.show', $c->user) }}">{{ $c->user->name }}</a>@endif</td>
                            <td>{{ $c->course?->title }}</td>
                            <td dir="ltr" style="text-align:right"><a href="{{ route('certificates.show', $c->code) }}" target="_blank">{{ $c->code }}</a></td>
                            <td class="sub">{{ $c->issued_at->format('Y-m-d') }}</td>
                            <td>@include('admin.partials.delete-button', ['action' => route('admin.certificates.destroy', $c), 'label' => 'إلغاء', 'confirm' => 'إلغاء الشهادة؟ لن يمكن التحقق منها بعد ذلك.'])</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $certificates->links() }}
        @endif
    </div>
@endsection
