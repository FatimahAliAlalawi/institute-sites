@extends('layouts.admin')

@section('title', 'تشغيل الدورة: '.$program->title)

@section('actions')
    <a href="{{ route('admin.registrations.index', ['program' => $program->id]) }}" class="btn"><i class="fa-solid fa-user-plus"></i> طلبات الدورة</a>
    <a href="{{ route('admin.programs.edit', $program) }}" class="btn"><i class="fa-regular fa-pen-to-square"></i> بيانات الدورة</a>
@endsection

@php
    $steps = \App\Models\Program::OPS_STEPS;
    $traineeSteps = \App\Models\Program::TRAINEE_STEPS;
    $total = $trainees->count();
    $today = now()->startOfDay();
    $phase = fn ($offset) => match (true) {
        $offset === null => 'عند كل تسجيل',
        $offset === 0 => 'يوم الدورة',
        $offset === -1 => 'قبل الدورة بيوم',
        default => 'قبل الدورة بـ '.abs($offset).' أيام',
    };
@endphp

@section('content')
    <div class="card" style="display:flex;gap:22px;flex-wrap:wrap;align-items:center">
        <div><div class="sub">تاريخ البدء</div><b>{{ $program->start_date?->translatedFormat('l j F Y') ?? 'غير محدد' }}</b></div>
        @if ($program->schedule_days)<div><div class="sub">الأيام</div><b>{{ $program->schedule_days }}</b></div>@endif
        @if ($program->schedule_time)<div><div class="sub">الوقت</div><b>{{ $program->schedule_time }}</b></div>@endif
        @if ($program->trainer_name)<div><div class="sub">المدرب</div><b>{{ $program->trainer_name }}</b></div>@endif
        <div><div class="sub">المتدربون</div><b>{{ $total }}{{ $program->seats ? ' من '.$program->seats : '' }}</b></div>
        @unless ($program->start_date)<div class="alert alert-error" style="margin:0">أضف تاريخ البدء في بيانات الدورة لتظهر مواعيد الخطوات.</div>@endunless
    </div>

    <form method="post" action="{{ route('admin.programs.ops.update', $program) }}">
        @csrf

        <div class="card">
            <h2>آلية التسجيل — من لحظة التسجيل حتى يوم الدورة</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>#</th><th>الخطوة</th><th>متى</th><th>المسؤول</th><th>الإنجاز</th></tr>
                    @php($lastPhase = '__')
                    @foreach ($steps as $key => [$title, $detail, $kind, $offset])
                        @php($due = $program->opsDue($key))
                        @php($doneCount = $kind === 'trainee' ? ($key === 'register' ? $total : $trainees->filter(fn ($t) => $t->stepDone($key))->count()) : null)
                        @php($complete = $kind === 'course' ? (bool) $program->opsCourseDone($key) : ($total > 0 && $doneCount === $total))
                        @php($late = $due && $due->lt($today) && ! $complete && ($kind === 'course' || $total > 0))
                        @if ($phase($offset) !== $lastPhase)
                            <tr><td colspan="5" style="background:#f6f5fb;font-weight:700">
                                {{ $offset === null ? '⏱ ' : ($offset === 0 ? '🌟 ' : ($offset === -1 ? '🌙 ' : '⏰ ')) }}{{ $phase($offset) }}
                                @if ($due)<span class="sub">— {{ $due->translatedFormat('l j F') }}</span>@endif
                            </td></tr>
                            @php($lastPhase = $phase($offset))
                        @endif
                        <tr @if ($late) style="background:#fdecec" @endif>
                            <td><b>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</b></td>
                            <td><b>{{ $title }}</b><div class="sub">{{ $detail }}</div></td>
                            <td class="sub" style="white-space:nowrap">{{ $due ? $due->format('Y-m-d') : 'مع كل متدرب' }}@if ($late)<div style="color:#b42318;font-weight:700">متأخرة</div>@endif</td>
                            <td><input type="text" name="assignees[{{ $key }}]" value="{{ old("assignees.$key", $program->opsAssignees($key)) }}" placeholder="اسم المسؤول" style="min-width:170px"></td>
                            <td style="white-space:nowrap">
                                @if ($kind === 'course')
                                    @php($d = $program->opsCourseDone($key))
                                    <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="done[{{ $key }}]" value="1" @checked($d)> تم</label>
                                    @if ($d)<div class="sub">{{ $d['by'] ?? '' }} — {{ $d['at'] ?? '' }}</div>@endif
                                @elseif ($total === 0)
                                    <span class="sub">لا متدربين بعد</span>
                                @else
                                    <span class="badge {{ $complete ? 'b-on' : 'b-new' }}">{{ $doneCount }} من {{ $total }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="form-grid" style="margin-top:14px">
                <div class="field">
                    <label for="group_link">رابط مجموعة الدورة (واتساب)</label>
                    <input type="url" id="group_link" name="group_link" value="{{ old('group_link', $program->group_link) }}" dir="ltr" placeholder="https://chat.whatsapp.com/...">
                    <div class="hint">يُرسل مع الفاتورة في الخطوة 03.</div>
                </div>
                <div class="field checks" style="align-self:end">
                    <label><input type="checkbox" name="save_default" value="1"> احفظ أسماء المسؤولين كافتراضي لكل الدورات</label>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>متابعة كل متدرب</h2>
            @if ($trainees->isEmpty())
                <div class="empty">لا يوجد متدربون مقبولون في هذه الدورة بعد. المتدرب يظهر هنا لما تكون حالته «مقبول (سجّل ودفع)».</div>
            @else
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>المتدرب</th>
                            @foreach ($traineeSteps as $s)
                                <th style="text-align:center;font-size:12px">{{ str_pad(array_search($s, array_keys($steps)) + 1, 2, '0', STR_PAD_LEFT) }}<br>{{ $steps[$s][0] }}</th>
                            @endforeach
                        </tr>
                        @foreach ($trainees as $t)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.registrations.show', $t) }}"><b>{{ $t->name }}</b></a>
                                    <div class="sub" dir="ltr" style="text-align:right">{{ $t->phone }} @if ($t->invoice_no)· {{ $t->invoice_no }}@endif</div>
                                </td>
                                @foreach ($traineeSteps as $s)
                                    <td style="text-align:center">
                                        <input type="checkbox" name="steps[{{ $t->id }}][{{ $s }}]" value="1" @checked($t->stepDone($s)) title="{{ $t->steps[$s]['by'] ?? '' }} {{ $t->steps[$s]['at'] ?? '' }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </div>
                <p class="sub">مرّر المؤشر على أي علامة لترى من نفّذها ومتى. خطوة «إنشاء الفاتورة» تُؤشَّر تلقائيًا إذا كُتب رقم الفاتورة في طلب المتدرب.</p>
            @endif
        </div>

        <div class="form-foot sticky-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ</button>
        </div>
    </form>
@endsection
