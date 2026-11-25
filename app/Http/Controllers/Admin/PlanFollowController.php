<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramAttendance;
use App\Models\ProgramTestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/** متابعة الخطة: المخطط مقابل المنفّذ لكل دورة معتمدة، وجاهزيتها، وأثرها (ثلاثة أشهر) */
class PlanFollowController extends Controller
{
    public const STATES = [
        'upcoming' => ['قادمة', 'b-new'],
        'running' => ['جارية الآن', 'b-contacted'],
        'done' => ['نُفّذت', 'b-on'],
        'unproven' => ['انتهى موعدها بلا تحضير', 'b-rejected'],
        'postponed' => ['مؤجلة', 'b-contacted'],
        'cancelled' => ['ملغاة', 'b-rejected'],
        'undated' => ['بلا تاريخ', 'b-off'],
    ];

    public function index(Request $request)
    {
        $start = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('from'))
            ? Carbon::createFromFormat('Y-m-d', $request->query('from').'-01')->startOfMonth()
            : now()->startOfMonth();
        $end = $start->copy()->addMonths(3);
        $rows = $this->rows($start, $end);

        $count = fn (string ...$s) => $rows->whereIn('state', $s)->count();
        // ما حان موعده من الخطة = نُفّذ أو أُلغي أو تأجل أو انتهى بلا تحضير
        $due = $count('done', 'unproven', 'cancelled', 'postponed');
        $kpi = [
            'planned' => $rows->count(),
            'done' => $count('done'),
            'running' => $count('running'),
            'upcoming' => $count('upcoming', 'undated'),
            'postponed' => $count('postponed'),
            'cancelled' => $count('cancelled'),
            'unproven' => $count('unproven'),
            'rate' => $due ? (int) round($count('done') / $due * 100) : null,
            'attention' => $rows->filter(fn ($r) => $r['alerts'])->count(),
        ];

        return view('admin.plan-follow.index', [
            'months' => $rows->groupBy('month')->sortKeys(),
            'kpi' => $kpi,
            'start' => $start,
            'end' => $end->copy()->subDay(),
            'prev' => $start->copy()->subMonths(3)->format('Y-m'),
            'next' => $end->format('Y-m'),
            'min' => Program::minTrainees(),
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'plan_exec' => ['nullable', Rule::in(array_keys(Program::PLAN_EXEC))],
            'plan_exec_note' => 'nullable|required_with:plan_exec|string|max:500',
        ], ['plan_exec_note.required_with' => 'اكتب سبب التأجيل أو الإلغاء.']);

        $program->plan_exec = $data['plan_exec'] ?? null;
        $program->plan_exec_note = $data['plan_exec_note'] ?? null;
        if ($program->plan_exec === 'cancelled') {
            $program->registration_open = false;
        }
        $program->save();

        return back()->with('status', $program->title.': تم الحفظ.'.($program->plan_exec === 'cancelled' ? ' أُغلق التسجيل وأُخفيت من صفحة الخطة.' : ''));
    }

    public function export(Request $request)
    {
        $rows = $this->index($request)->getData()['months']->flatten(1);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الدورة', 'الموعد المخطط', 'الموعد الحالي', 'الانحراف (يوم)', 'الحالة', 'المسجلون', 'المقاعد', 'الحضور %', 'القبلي %', 'البعدي %', 'التحسن (نقطة)', 'تنبيهات', 'ملاحظة']);
            foreach ($rows as $r) {
                $p = $r['program'];
                fputcsv($out, [
                    $p->title, $p->plan_baseline_date?->format('Y-m-d'), $p->start_date?->format('Y-m-d'), $r['shift'],
                    self::STATES[$r['state']][0], $r['registered'], $p->seats, $r['attendance'],
                    $r['test']['pre_avg'], $r['test']['post_avg'], $r['test']['gain'], implode('، ', $r['alerts']), $p->plan_exec_note,
                ]);
            }
            fclose($out);
        }, 'متابعة-الخطة.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function rows(Carbon $start, Carbon $end): Collection
    {
        $programs = Program::query()
            ->where(fn ($q) => $q->where('plan_status', 'approved')->orWhereNotNull('plan_baseline_date'))
            ->withCount(['seatRegistrations', 'questions'])
            ->get()
            ->filter(function (Program $p) use ($start, $end) {
                $date = $p->plan_baseline_date ?? $p->start_date;

                // بلا تاريخ: تظهر في الفترة الحالية فقط
                return $date ? $date->gte($start) && $date->lt($end) : now()->between($start, $end);
            });

        $ids = $programs->pluck('id');
        $att = ProgramAttendance::whereIn('program_id', $ids)->get(['program_id', 'status'])->groupBy('program_id');
        $tests = ProgramTestResult::whereIn('program_id', $ids)
            ->whereHas('registration', fn ($q) => $q->whereIn('status', \App\Models\Registration::SEAT_STATUSES))
            ->get()->groupBy('program_id');
        $today = now()->startOfDay();

        return $programs->map(function (Program $p) use ($att, $tests, $today) {
            $a = $att->get($p->id, collect());
            $state = $this->state($p, $a->isNotEmpty(), $today);
            $test = ProgramTestResult::summarize($tests->get($p->id, collect()));
            $registered = (int) $p->seat_registrations_count;
            $date = $p->plan_baseline_date ?? $p->start_date;

            return [
                'program' => $p,
                'month' => $date?->format('Y-m') ?? '9999-99',
                'sort' => $date?->timestamp ?? PHP_INT_MAX,
                'state' => $state,
                'shift' => $p->plan_baseline_date && $p->start_date ? (int) $p->plan_baseline_date->copy()->startOfDay()->diffInDays($p->start_date->copy()->startOfDay(), false) : null,
                'registered' => $registered,
                'attendance' => $a->isNotEmpty() ? (int) round($a->whereIn('status', ProgramAttendance::ATTENDED)->count() / $a->count() * 100) : null,
                'test' => $test,
                'alerts' => $this->alerts($p, $state, $registered, $test, $today),
            ];
        })->sortBy('sort')->values();
    }

    protected function state(Program $p, bool $hasAttendance, Carbon $today): string
    {
        if ($p->plan_exec) {
            return $p->plan_exec;
        }
        if (! $p->start_date) {
            return 'undated';
        }
        $first = $p->session_dates[0] ?? $p->start_date->toDateString();
        $last = $p->session_dates ? max($p->session_dates) : $p->start_date->toDateString();
        $now = $today->toDateString();

        return match (true) {
            $now < $first => 'upcoming',
            $now <= $last => 'running',
            default => $hasAttendance ? 'done' : 'unproven',
        };
    }

    /** ما ينقص الدورة الآن */
    protected function alerts(Program $p, string $state, int $registered, array $test, Carbon $today): array
    {
        $alerts = [];
        if (in_array($state, ['cancelled', 'postponed'], true)) {
            return $alerts;
        }
        if ($p->plan_status !== 'approved') {
            $alerts[] = 'تعدّلت وتنتظر إعادة اعتماد الخطة';
        }
        if ($state === 'undated') {
            $alerts[] = 'بلا تاريخ بدء';
        }
        if (in_array($state, ['upcoming', 'undated'], true)) {
            if (! $p->accreditation_no) {
                $alerts[] = 'رقم الاعتماد ناقص';
            }
            if (! $p->trainer_name && ! $p->trainer_admin_id) {
                $alerts[] = 'المدرب غير محدد';
            }
            if (! $p->session_dates) {
                $alerts[] = 'أيام الدورة غير مضافة للتقويم';
            }
            if ($p->ad_status !== 'approved') {
                $alerts[] = 'الإعلان غير معتمد';
            }
        }
        if (in_array($state, ['upcoming', 'running', 'undated'], true) && ! $p->questions_count) {
            $alerts[] = 'أسئلة الاختبار لم تُضف';
        }
        if ($state === 'upcoming' && $registered < Program::minTrainees() && $today->diffInDays($p->start_date, false) <= 7) {
            $alerts[] = 'أقل من الحد الأدنى ('.$registered.' من '.Program::minTrainees().') وباقي أسبوع أو أقل: قرار تُعقد أو تؤجل';
        }
        if (in_array($state, ['running', 'done'], true) && $test['pre_count'] < $registered) {
            $alerts[] = 'القبلي: باقي '.($registered - $test['pre_count']);
        }
        if ($state === 'done' && $test['post_count'] < $registered) {
            $alerts[] = 'البعدي: باقي '.($registered - $test['post_count']);
        }
        if ($state === 'unproven') {
            $alerts[] = 'سجّل التحضير، أو حدّد أنها تأجلت أو أُلغيت';
        }

        return $alerts;
    }
}
