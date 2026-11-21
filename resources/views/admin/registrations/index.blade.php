@extends('layouts.admin')

@section('title', 'طلبات التسجيل وقائمة المتابعة')

@section('actions')
    <a href="{{ route('admin.registrations.create', array_filter(['program' => is_numeric(request('program')) ? request('program') : null])) }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> إضافة مهتم</a>
    <a href="{{ route('admin.registrations.import') }}" class="btn"><i class="fa-solid fa-file-import"></i> نقل من نموذج Google / Excel</a>
    <a href="{{ route('admin.registrations.export', request()->query()) }}" class="btn"><i class="fa-solid fa-file-excel"></i> تصدير Excel</a>
@endsection

@section('content')
    <div class="tabs">
        <a href="{{ route('admin.registrations.index', request()->except('status', 'view', 'page')) }}" @class(['active' => ! request('status') && request('view') !== 'due'])>الكل</a>
        <a href="{{ route('admin.registrations.index', ['view' => 'due'] + request()->except('status', 'view', 'page')) }}" @class(['active' => request('view') === 'due'])>
            <i class="fa-regular fa-bell"></i> متابعات اليوم @if ($dueCount)<span class="badge b-rejected">{{ $dueCount }}</span>@endif
        </a>
        @foreach (\App\Models\Registration::STATUSES as $key => $label)
            <a href="{{ route('admin.registrations.index', ['status' => $key] + request()->except('status', 'view', 'page')) }}" @class(['active' => request('status') === $key])>{{ $label }}</a>
        @endforeach
    </div>

    @if ($seatInfo)
        <div class="card" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
            <b>{{ $seatInfo->title }}</b>
            <span>المسجّلون: <b>{{ $seatInfo->seat_registrations_count }}</b>{{ $seatInfo->seats ? ' من '.$seatInfo->seats.' مقعدًا' : ' (لم يُحدد عدد المقاعد)' }}</span>
            @if ($seatInfo->seats)
                @php($pct = min(100, (int) round($seatInfo->seat_registrations_count / max(1, $seatInfo->seats) * 100)))
                <span style="flex:1;min-width:160px;height:10px;background:#eceaf3;border-radius:999px;overflow:hidden"><span style="display:block;height:100%;width:{{ $pct }}%;background:{{ $pct >= 100 ? '#b42318' : 'var(--primary, #32255D)' }}"></span></span>
                <span class="badge {{ $pct >= 100 ? 'b-rejected' : 'b-on' }}">{{ $pct >= 100 ? 'مكتملة' : 'باقي '.max(0, $seatInfo->seats - $seatInfo->seat_registrations_count) }}</span>
            @endif
            <span class="badge {{ $seatInfo->registration_open ? 'b-on' : 'b-off' }}">{{ $seatInfo->registration_open ? 'التسجيل مفتوح' : 'التسجيل مغلق' }}</span>
        </div>
    @endif

    <div class="card">
        <form class="filters" method="get">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <div class="field">
                <label>بحث</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="الاسم أو الجوال أو البريد">
            </div>
            <div class="field">
                <label>البرنامج</label>
                <select name="program">
                    <option value="">كل البرامج</option>
                    <option value="none" @selected(request('program') === 'none')>تسجيل عام</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}" @selected((string) request('program') === (string) $p->id)>{{ $p->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>المصدر</label>
                <select name="source">
                    <option value="">كل المصادر</option>
                    @foreach (\App\Models\Registration::SOURCES as $src)
                        <option @selected(request('source') === $src)>{{ $src }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> تصفية</button>
            @if (request()->hasAny(['q', 'program', 'source']))<a href="{{ route('admin.registrations.index', array_filter(['view' => request('view'), 'status' => request('status')])) }}" class="btn">مسح</a>@endif
        </form>

        @if (request('view') === 'due')
            <p class="sub" style="margin:0 0 10px">من موعد متابعته اليوم أو فات موعده، وحالته ما زالت مفتوحة (جديد، أرسلنا العرض، لم يرد). المتأخر بالأحمر.</p>
        @endif

        @if ($registrations->isEmpty())
            <div class="empty">{{ request('view') === 'due' ? 'لا توجد متابعات مستحقة اليوم. 👌' : 'لا توجد طلبات مطابقة.' }}</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>#</th><th>الاسم</th><th>الجوال</th><th>البرنامج</th><th>الحالة</th><th>المصدر</th><th>المتابعة القادمة</th><th>التاريخ</th><th></th></tr>
                    @foreach ($registrations as $r)
                        <tr>
                            <td class="sub">{{ $r->id }}</td>
                            <td><a href="{{ route('admin.registrations.show', $r) }}"><b>{{ $r->name }}</b></a><div class="sub">{{ $r->city }}</div></td>
                            <td dir="ltr" style="text-align:right"><a href="tel:{{ $r->phone }}">{{ $r->phone }}</a></td>
                            <td>{{ $r->program?->title ?? 'تسجيل عام' }}</td>
                            <td>
                                <span class="badge b-{{ $r->status }}">{{ $r->status_label }}</span>
                                @if ($r->attendance_label)<div class="sub">{{ $r->attendance_label }}</div>@endif
                            </td>
                            <td class="sub">{{ $r->source ?? '—' }}</td>
                            <td class="sub" style="white-space:nowrap">
                                @if ($r->follow_up_at && in_array($r->status, \App\Models\Registration::OPEN_STATUSES, true))
                                    <span @if ($r->is_overdue) style="color:#b42318;font-weight:700" @endif>{{ $r->follow_up_at->format('Y-m-d') }}{{ $r->follow_up_at->isToday() ? ' (اليوم)' : '' }}</span>
                                @else — @endif
                            </td>
                            <td class="sub" style="white-space:nowrap">{{ $r->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.registrations.show', $r) }}" class="btn btn-sm">متابعة</a>
                                    <a href="{{ $r->whatsapp_link }}" target="_blank" rel="noopener" class="btn btn-sm" title="واتساب"><i class="fa-brands fa-whatsapp"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $registrations->links() }}
        @endif
    </div>
@endsection
