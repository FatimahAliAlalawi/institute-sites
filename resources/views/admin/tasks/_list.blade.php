@if ($tasks->isEmpty())
    <div class="empty">لا توجد مهام مسندة. 👌</div>
@else
    <div class="table-wrap">
        <table>
            <tr><th>المهمة</th><th>الدورة</th><th>الموعد</th><th>الإنجاز</th><th></th></tr>
            @foreach ($tasks as $t)
                <tr @if ($t['overdue']) style="background:#fdecec" @elseif ($t['today']) style="background:#fff8ec" @endif>
                    <td><b>{{ $t['title'] }}</b><div class="sub">{{ $t['detail'] }}</div></td>
                    <td>{{ $t['program']->title }}<div class="sub">{{ $t['program']->start_date?->format('Y-m-d') ?? 'بدون تاريخ' }}</div></td>
                    <td class="sub" style="white-space:nowrap">
                        {{ $t['due'] ? $t['due']->format('Y-m-d') : 'مع كل متدرب' }}
                        @if ($t['overdue'])<div style="color:#b42318;font-weight:700">متأخرة</div>@elseif ($t['today'])<div style="color:#b54708;font-weight:700">اليوم</div>@endif
                    </td>
                    <td style="white-space:nowrap">
                        @if ($t['done'])
                            <span class="badge b-on">تمت</span>
                        @elseif ($t['kind'] === 'trainee')
                            <span class="badge b-new">باقي {{ $t['pending'] }} من {{ $t['total'] }}</span>
                        @else
                            <span class="badge b-off">لم تُنفّذ</span>
                        @endif
                    </td>
                    <td><a href="{{ $t['link'] }}" class="btn btn-sm">فتح</a></td>
                </tr>
            @endforeach
        </table>
    </div>
@endif
