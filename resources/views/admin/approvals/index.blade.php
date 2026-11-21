@extends('layouts.admin')

@section('title', 'اعتماد الخطة التدريبية والإعلانات')

@section('actions')
    <a href="{{ route('programs.plan') }}" target="_blank" class="btn"><i class="fa-regular fa-calendar-check"></i> عرض خطة الأفراد في الموقع</a>
@endsection

@section('content')
    <div class="card">
        <h2>بانتظار اعتماد الإدارة ({{ $pending->count() }})</h2>
        @if ($pending->isEmpty())
            <div class="empty">لا يوجد شيء بانتظار الاعتماد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>البرنامج</th><th>البدء</th><th>الرسوم</th><th>رقم الاعتماد</th><th>الطلب</th><th>القرار</th></tr>
                    @foreach ($pending as $program)
                        @foreach (['plan' => 'إدراج في الخطة', 'ad' => 'نشر الإعلان'] as $kind => $kindLabel)
                            @continue($program->{$kind.'_status'} !== 'pending')
                            <tr>
                                <td>
                                    <a href="{{ route('admin.programs.edit', $program) }}"><b>{{ $program->title }}</b></a>
                                    <div class="sub">{{ $program->category_label }} · {{ $program->duration }}</div>
                                </td>
                                <td class="sub" style="white-space:nowrap">{{ $program->start_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="sub">
                                    @if ($program->sale_price !== null && $program->price !== null && (float) $program->sale_price < (float) $program->price)
                                        {{ number_format((float) $program->sale_price, 0) }} بدل {{ $program->price_label }}<br>حتى {{ $program->offer_ends_at?->format('Y-m-d') ?? '—' }}
                                    @else
                                        {{ $program->price_label ?? '—' }}
                                    @endif
                                </td>
                                <td class="sub">{!! $program->accreditation_no ? e($program->accreditation_no) : '<span class="badge b-rejected">ناقص</span>' !!}</td>
                                <td>
                                    <span class="badge b-new">{{ $kindLabel }}</span>
                                    @if ($kind === 'ad')<div><a href="{{ route('admin.programs.ad-card', $program) }}" target="_blank" class="sub">معاينة بطاقة الإعلان</a></div>@endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('admin.approvals.decide', $program) }}" class="actions" style="flex-wrap:wrap">
                                        @csrf
                                        <input type="hidden" name="kind" value="{{ $kind }}">
                                        <input type="text" name="note" placeholder="ملاحظة (مطلوبة عند الرفض)" style="min-width:170px">
                                        <button name="decision" value="approved" class="btn btn-sm btn-primary"><i class="fa-solid fa-check"></i> اعتماد</button>
                                        <button name="decision" value="rejected" class="btn btn-sm"><i class="fa-solid fa-xmark"></i> رفض</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <h2>الخطة المعتمدة حاليًا ({{ $plan->count() }})</h2>
        @if ($plan->isEmpty())
            <div class="empty">لا توجد دورات معتمدة في الخطة بعد. من «البرامج التدريبية» اختر لكل دورة: الخطة ← إرسال للإدارة للاعتماد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>البرنامج</th><th>البدء</th><th>اعتمدها</th><th>الإعلان</th><th></th></tr>
                    @foreach ($plan as $program)
                        <tr>
                            <td><a href="{{ route('admin.programs.edit', $program) }}"><b>{{ $program->title }}</b></a> @unless ($program->is_active)<span class="badge b-off">مخفي في الموقع</span>@endunless</td>
                            <td class="sub">{{ $program->start_date?->format('Y-m-d') ?? 'بدون تاريخ' }}</td>
                            <td class="sub">{{ $program->plan_approved_by }} — {{ $program->plan_approved_at?->format('Y-m-d') }}</td>
                            <td><span class="badge {{ $program->ad_status === 'approved' ? 'b-on' : ($program->ad_status === 'rejected' ? 'b-rejected' : 'b-off') }}">{{ $program->ad_status_label }}</span></td>
                            <td>
                                <form method="post" action="{{ route('admin.approvals.decide', $program) }}">
                                    @csrf
                                    <input type="hidden" name="kind" value="plan">
                                    <button name="decision" value="none" class="btn btn-sm" onclick="return confirm('إخراج الدورة من الخطة؟')">إخراج من الخطة</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
