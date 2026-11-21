@extends('layouts.admin')

@section('title', 'الاشتراكات والمدفوعات')

@section('content')
    <div class="tabs">
        <a href="{{ route('admin.enrollments.index', request()->except('status', 'page')) }}" @class(['active' => ! request('status')])>الكل</a>
        @foreach (\App\Models\Enrollment::STATUSES as $key => $label)
            <a href="{{ route('admin.enrollments.index', ['status' => $key] + request()->except('status', 'page')) }}" @class(['active' => request('status') === $key])>
                {{ $label }} @if ($key === 'pending' && ($badges['enrollments'] ?? 0))<span class="badge b-rejected">{{ $badges['enrollments'] }}</span>@endif
            </a>
        @endforeach
    </div>

    <div class="stats">
        <div class="stat"><b>{{ number_format((float) $revenue, 2) }}</b><span>إجمالي التحويلات المؤكدة (ر.س)</span></div>
        <div class="stat"><b>{{ $badges['enrollments'] ?? 0 }}</b><span>بانتظار المراجعة</span></div>
    </div>

    <div class="card">
        <form class="filters" method="get">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="field"><label>الطالب</label><input type="search" name="q" value="{{ request('q') }}" placeholder="الاسم أو البريد أو الجوال"></div>
            <div class="field">
                <label>الدورة</label>
                <select name="course">
                    <option value="">كل الدورات</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected((string) request('course') === (string) $c->id)>{{ $c->title }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> تصفية</button>
        </form>

        @if ($enrollments->isEmpty())
            <div class="empty">لا توجد اشتراكات مطابقة.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الطالب</th><th>الدورة</th><th>الدفع</th><th>الإيصال</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr>
                    @foreach ($enrollments as $e)
                        <tr>
                            <td>@if ($e->user)<a href="{{ route('admin.students.show', $e->user) }}"><b>{{ $e->user->name }}</b></a><div class="sub" dir="ltr" style="text-align:right">{{ $e->user->phone }}</div>@endif</td>
                            <td>{{ $e->course?->title }}</td>
                            <td>{{ $e->method_label }}@if ($e->amount > 0)<div class="sub">{{ number_format((float) $e->amount, 2) }} ر.س</div>@endif</td>
                            <td>
                                @if ($e->receipt)<a href="{{ route('admin.enrollments.receipt', $e) }}" target="_blank" class="btn btn-sm"><i class="fa-solid fa-file-invoice"></i> عرض</a>@else<span class="sub">—</span>@endif
                                @if ($e->note)<div class="sub" title="{{ $e->note }}">{{ \Illuminate\Support\Str::limit($e->note, 40) }}</div>@endif
                            </td>
                            <td><span class="badge {{ ['active' => 'b-accepted', 'pending' => 'b-contacted', 'cancelled' => 'b-rejected'][$e->status] ?? 'b-off' }}">{{ $e->status_label }}</span></td>
                            <td class="sub">{{ $e->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="actions">
                                    @if ($e->status !== 'active')
                                        <form method="post" action="{{ route('admin.enrollments.update', $e) }}">@csrf @method('PUT')<input type="hidden" name="status" value="active"><button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-check"></i> تفعيل</button></form>
                                    @endif
                                    @if ($e->status !== 'cancelled')
                                        <form method="post" action="{{ route('admin.enrollments.update', $e) }}" data-confirm="إلغاء هذا الاشتراك؟">@csrf @method('PUT')<input type="hidden" name="status" value="cancelled"><button class="btn btn-sm" type="submit">إلغاء</button></form>
                                    @endif
                                    @include('admin.partials.delete-button', ['action' => route('admin.enrollments.destroy', $e), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $enrollments->links() }}
        @endif
    </div>
@endsection
