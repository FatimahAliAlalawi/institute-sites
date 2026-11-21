@extends('layouts.admin')

@section('title', 'تقارير الأداء')

@section('actions')
    <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير الدورات Excel</a>
    <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> طباعة</button>
@endsection

@section('content')
    @php($bar = fn ($pct, $color = '#32255D') => '<span style="display:block;height:8px;background:#eceaf3;border-radius:999px;overflow:hidden;min-width:90px"><span style="display:block;height:100%;width:'.min(100, (int) $pct).'%;background:'.$color.'"></span></span>')

    <div class="card">
        <form class="filters" method="get">
            <div class="field">
                <label>الفترة</label>
                <select name="period" onchange="document.getElementById('customRange').style.display = this.value === 'custom' ? 'contents' : 'none'">
                    @foreach (\App\Http\Controllers\Admin\ReportController::PERIODS as $key => $label)
                        <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <span id="customRange" style="display:{{ $period === 'custom' ? 'contents' : 'none' }}">
                <div class="field"><label>من</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
                <div class="field"><label>إلى</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
            </span>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-chart-column"></i> عرض</button>
        </form>
        <p class="sub" style="margin:8px 0 0">من {{ $from->format('Y-m-d') }} إلى {{ $to->format('Y-m-d') }} — الأرقام حسب تاريخ دخول الطلب للموقع.</p>
    </div>

    <div class="stats">
        <div class="stat"><b>{{ $kpi['interested'] }}</b><span>مهتم / طلب جديد</span></div>
        <div class="stat"><b>{{ $kpi['registered'] }}</b><span>مسجّل (مقبول أو أكمل)</span></div>
        <div class="stat"><b>{{ $kpi['conversion'] }}%</b><span>نسبة التحويل (من مهتم إلى مسجّل)</span></div>
        <div class="stat"><b>{{ number_format($kpi['revenue']) }}</b><span>الإيراد المتوقع (ر.س)</span></div>
        <div class="stat"><b>{{ $kpi['attendance'] !== null ? $kpi['attendance'].'%' : '—' }}</b><span>نسبة الحضور في الدورات</span></div>
        <a class="stat" href="{{ route('admin.registrations.index', ['view' => 'due']) }}"><b @if ($kpi['overdue']) style="color:#b42318" @endif>{{ $kpi['overdue'] }}</b><span>متابعة متأخرة الآن</span></a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px">
        <div class="card">
            <h2>من وين يجون المتدربين؟ (المصدر)</h2>
            @if ($sources->isEmpty())
                <div class="empty">لا توجد طلبات في هذه الفترة.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <tr><th>المصدر</th><th>مهتم</th><th>مسجّل</th><th>التحويل</th></tr>
                        @foreach ($sources as $s)
                            <tr>
                                <td><b>{{ $s['name'] }}</b></td>
                                <td>{{ $s['interested'] }}</td>
                                <td>{{ $s['registered'] }}</td>
                                <td>{!! $bar($s['conversion'], '#16794a') !!}<span class="sub">{{ $s['conversion'] }}%</span></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <p class="sub" style="margin:8px 0 0">المصدر الأعلى تحويلًا يستحق ميزانية تسويق أكبر.</p>
            @endif
        </div>

        <div class="card">
            <h2>وين وصلت الطلبات؟ (الحالة)</h2>
            @if ($statuses->isEmpty())
                <div class="empty">لا توجد طلبات في هذه الفترة.</div>
            @else
                <div class="table-wrap">
                    <table>
                        @foreach ($statuses as $s)
                            <tr>
                                <td style="width:140px"><span class="badge b-{{ $s['key'] }}">{{ $s['label'] }}</span></td>
                                <td>{!! $bar(round($s['count'] / max(1, $kpi['interested']) * 100)) !!}</td>
                                <td style="width:50px"><b>{{ $s['count'] }}</b></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <p class="sub" style="margin:8px 0 0">مفتوحة تنتظر متابعة: <b>{{ $kpi['open'] }}</b> — انسحب أو رُفض: <b>{{ $kpi['lost'] }}</b></p>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>الدورات</h2>
        @if ($courseRows->isEmpty())
            <div class="empty">لا توجد دورات تبدأ في هذه الفترة أو عليها طلبات.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الدورة</th><th>البدء</th><th>المهتمون</th><th>المسجّلون</th><th>التحويل</th><th>امتلاء المقاعد</th><th>الحضور</th><th>الإيراد المتوقع</th></tr>
                    @foreach ($courseRows as $r)
                        @php($p = $r['program'])
                        <tr>
                            <td><a href="{{ route('admin.registrations.index', ['program' => $p->id]) }}"><b>{{ $p->title }}</b></a></td>
                            <td class="sub" style="white-space:nowrap">{{ $p->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $r['interested'] }}</td>
                            <td>{{ $r['registered'] }}{{ $p->seats ? ' / '.$p->seats : '' }}</td>
                            <td>{{ $r['conversion'] }}%</td>
                            <td>
                                @if ($r['fill'] !== null)
                                    {!! $bar($r['fill'], $r['fill'] >= 100 ? '#16794a' : '#32255D') !!}<span class="sub">{{ $r['fill'] }}%</span>
                                @else <span class="sub">لم تُحدد المقاعد</span> @endif
                            </td>
                            <td>{{ $r['attendance'] !== null ? $r['attendance'].'%' : '—' }}</td>
                            <td style="white-space:nowrap">{{ number_format($r['revenue']) }} ر.س</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <p class="sub" style="margin:8px 0 0">الإيراد المتوقع = عدد المسجّلين × سعر الدورة (سعر العرض إن وُجد). ليس مبلغًا محصّلًا فعليًا.</p>
        @endif
    </div>

    <div class="card">
        <h2>آخر 6 أشهر</h2>
        <div style="display:flex;gap:14px;align-items:flex-end;height:190px;padding-top:10px">
            @foreach ($months as $m)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%">
                    <div style="flex:1;width:100%;display:flex;gap:4px;align-items:flex-end;justify-content:center">
                        <span title="مهتم: {{ $m['interested'] }}" style="width:38%;max-width:34px;background:#cfc8e6;border-radius:6px 6px 0 0;height:{{ max(2, round($m['interested'] / $maxMonth * 100)) }}%"></span>
                        <span title="مسجّل: {{ $m['registered'] }}" style="width:38%;max-width:34px;background:#32255D;border-radius:6px 6px 0 0;height:{{ max(2, round($m['registered'] / $maxMonth * 100)) }}%"></span>
                    </div>
                    <b style="font-size:13px">{{ $m['interested'] }} / {{ $m['registered'] }}</b>
                    <span class="sub" style="font-size:12px;white-space:nowrap">{{ $m['label'] }}</span>
                </div>
            @endforeach
        </div>
        <p class="sub" style="margin:10px 0 0"><span style="display:inline-block;width:10px;height:10px;background:#cfc8e6;border-radius:3px"></span> مهتم &nbsp; <span style="display:inline-block;width:10px;height:10px;background:#32255D;border-radius:3px"></span> مسجّل</p>
    </div>
@endsection
