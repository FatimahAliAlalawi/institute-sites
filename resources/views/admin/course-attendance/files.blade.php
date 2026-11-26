@extends('layouts.admin')

@section('title', 'الخطط والتقارير: '.$program->title)

@section('actions')
    <a href="{{ route('admin.course-attendance.show', $program) }}" class="btn"><i class="fa-solid fa-clipboard-check"></i> التحضير</a>
    <a href="{{ route('admin.course-attendance.tests', $program) }}" class="btn"><i class="fa-solid fa-file-pen"></i> الاختبار القبلي والبعدي</a>
@endsection

@php
    $kinds = \App\Models\ProgramReport::KINDS;
    $byKey = $reports->groupBy(fn ($r) => $r->kind.'|'.$r->week?->toDateString());
    $weekNo = array_flip(array_keys($weeks));
    $weekLabel = fn ($w) => 'الأسبوع '.($weekNo[$w] + 1).' ('.\Illuminate\Support\Carbon::parse($weeks[$w][0])->format('m/d').' – '.\Illuminate\Support\Carbon::parse(end($weeks[$w]))->format('m/d').')';
    $today = now()->toDateString();
@endphp

@section('content')
    <div class="card">
        <h2>المطلوب في هذه الدورة</h2>
        @if (! $weeks)
            <div class="empty">ما فيه أيام للدورة في التقويم بعد. أضف تاريخ البدء وأيام الدورة من «تشغيل الدورة».</div>
        @else
            <div class="table-wrap"><table>
                <tr><th>المطلوب</th>@foreach ($weeks as $w => $days)<th>{{ $weekLabel($w) }}</th>@endforeach</tr>
                @foreach (['weekly_plan', 'weekly_report'] as $k)
                    <tr><td><b>{{ $kinds[$k][0] }}</b></td>
                        @foreach ($weeks as $w => $days)
                            <td>
                                @if ($byKey->has($k.'|'.$w))
                                    <span class="badge b-on">تم الرفع</span>
                                @else
                                    @php($due = $k === 'weekly_plan' ? $days[0] : \Illuminate\Support\Carbon::parse(end($days))->addDay()->toDateString())
                                    <span class="badge {{ $due < $today ? 'b-off' : 'b-new' }}">{{ $due < $today ? 'متأخر' : 'قبل '.$due }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                @foreach (['pre_report', 'post_report'] as $k)
                    <tr><td><b>{{ $kinds[$k][0] }}</b></td>
                        <td colspan="{{ count($weeks) }}">
                            @if ($byKey->has($k.'|'))<span class="badge b-on">تم الرفع</span>@else<span class="badge b-new">لم يُرفع</span>@endif
                        </td>
                    </tr>
                @endforeach
            </table></div>
        @endif
    </div>

    <div class="card">
        <h2><i class="fa-solid fa-upload"></i> رفع ملف</h2>
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.course-attendance.files.store', $program) }}">
            @csrf
            <div class="filters">
                <div class="field"><label for="kind">النوع</label>
                    <select name="kind" id="kind" onchange="document.getElementById('weekBox').style.display = {{ json_encode(array_keys(array_filter($kinds, fn ($k) => $k[1]))) }}.includes(this.value) ? '' : 'none'">
                        @foreach ($kinds as $k => [$label])
                            <option value="{{ $k }}" @selected(old('kind', $kind) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" id="weekBox" @unless ($kinds[old('kind', $kind)][1]) style="display:none" @endunless><label for="week">الأسبوع</label>
                    <select name="week" id="week">
                        @foreach ($weeks as $w => $days)
                            <option value="{{ $w }}" @selected(old('week', $week) === $w)>{{ $weekLabel($w) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="file">الملف (PDF أو Word أو Excel أو صورة، حتى 20 ميجا)</label><input type="file" name="file" id="file"></div>
            </div>
            <div class="field"><label for="note">أو اكتب التقرير هنا / ملاحظة مع الملف</label><textarea name="note" id="note" rows="4">{{ old('note') }}</textarea></div>
            <button class="btn btn-primary"><i class="fa-solid fa-upload"></i> رفع</button>
        </form>
    </div>

    <div class="card">
        <h2>الملفات المرفوعة</h2>
        @if ($reports->isEmpty())
            <div class="empty">لم يُرفع شيء بعد.</div>
        @else
            <div class="table-wrap"><table>
                <tr><th>النوع</th><th>الأسبوع</th><th>الملف / التقرير</th><th>رفعه</th><th></th></tr>
                @foreach ($reports as $r)
                    <tr>
                        <td>{{ $r->kind_label }}</td>
                        <td class="sub">{{ $r->week && isset($weekNo[$r->week->toDateString()]) ? $weekLabel($r->week->toDateString()) : ($r->week?->format('Y-m-d') ?? '—') }}</td>
                        <td>
                            @if ($r->path)<a href="{{ route('admin.course-attendance.files.download', [$program, $r]) }}"><i class="fa-solid fa-file-arrow-down"></i> {{ $r->original_name }}</a>@endif
                            @if ($r->note)<div class="sub" style="white-space:pre-line">{{ $r->note }}</div>@endif
                        </td>
                        <td class="sub">{{ $r->admin_name }}<div>{{ $r->created_at?->format('Y-m-d H:i') }}</div></td>
                        <td>
                            @if (! $admin?->isTrainerOnly() || $r->admin_id === $admin->id)
                                <form method="post" action="{{ route('admin.course-attendance.files.destroy', [$program, $r]) }}" data-confirm="حذف هذا الملف؟">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table></div>
        @endif
    </div>
@endsection
