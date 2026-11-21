@extends('layouts.admin')

@section('title', 'الرئيسية')

@section('content')
    <div class="stats">
        @foreach ($stats as $stat)
            <a class="stat" href="{{ $stat['link'] }}"><b>{{ $stat['value'] }}</b><span>{{ $stat['label'] }}</span></a>
        @endforeach
    </div>

    @if ($currentAdmin->allows('registrations') || $currentAdmin->allows('messages'))
    <div class="cols">
        @if ($currentAdmin->allows('registrations'))
        <div class="card">
            <div class="top" style="margin-bottom:10px">
                <h2 style="margin:0">أحدث طلبات التسجيل</h2>
                <a href="{{ route('admin.registrations.index') }}" class="btn btn-sm">عرض الكل</a>
            </div>
            @if ($latestRegistrations->isEmpty())
                <div class="empty">لا توجد طلبات بعد.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <tr><th>الاسم</th><th>البرنامج</th><th>الحالة</th><th>التاريخ</th></tr>
                        @foreach ($latestRegistrations as $r)
                            <tr>
                                <td><a href="{{ route('admin.registrations.show', $r) }}">{{ $r->name }}</a><div class="sub" dir="ltr" style="text-align:right">{{ $r->phone }}</div></td>
                                <td>{{ $r->program?->title ?? 'تسجيل عام' }}</td>
                                <td><span class="badge b-{{ $r->status }}">{{ $r->status_label }}</span></td>
                                <td class="sub">{{ $r->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>

        @endif
        <div>
            @if ($currentAdmin->allows('registrations'))
            <div class="card">
                <h2>الطلبات حسب البرنامج</h2>
                @forelse ($byProgram as $p)
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)">
                        <span>{{ $p->title }}</span><b>{{ $p->registrations_count }}</b>
                    </div>
                @empty
                    <div class="empty">لا توجد برامج.</div>
                @endforelse
            </div>
            @endif
            @if ($currentAdmin->allows('messages'))
            <div class="card">
                <h2>أحدث الرسائل</h2>
                @forelse ($latestMessages as $m)
                    <a href="{{ route('admin.messages.show', $m) }}" style="display:block;padding:8px 0;border-bottom:1px solid var(--line);color:var(--ink)">
                        <b>{{ $m->name }}</b> @unless ($m->is_read)<span class="badge b-new">جديدة</span>@endunless
                        <div class="sub">{{ \Illuminate\Support\Str::limit($m->subject ?: $m->body, 60) }}</div>
                    </a>
                @empty
                    <div class="empty">لا توجد رسائل.</div>
                @endforelse
            </div>
            @endif
        </div>
    </div>
    @endif
@endsection
