<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramAttendance;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** تقارير الأداء: المهتمون، المسجّلون، نسبة التحويل، المصادر، الدورات، الحضور، الإيراد المتوقع */
class ReportController extends Controller
{
    public const PERIODS = [
        'month' => 'هذا الشهر',
        'last_month' => 'الشهر الماضي',
        'quarter' => 'آخر 3 أشهر',
        'year' => 'هذه السنة',
        'custom' => 'فترة مخصصة',
    ];

    public function index(Request $request)
    {
        [$period, $from, $to] = $this->range($request);

        $regs = Registration::with('program')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $seat = fn ($r) => in_array($r->status, Registration::SEAT_STATUSES, true);
        $kpi = [
            'interested' => $regs->count(),
            'registered' => $regs->filter($seat)->count(),
            'open' => $regs->whereIn('status', Registration::OPEN_STATUSES)->count(),
            'lost' => $regs->whereIn('status', ['withdrawn', 'rejected'])->count(),
            'overdue' => Registration::dueFollowUp()->get()->filter->is_overdue->count(),
        ];
        $kpi['conversion'] = $this->pct($kpi['registered'], $kpi['interested']);
        $kpi['revenue'] = $regs->filter($seat)->sum(fn ($r) => $this->priceOf($r->program));

        // حسب المصدر
        $sources = $regs->groupBy(fn ($r) => $r->source ?: 'غير محدد')
            ->map(fn ($g, $name) => [
                'name' => $name,
                'interested' => $g->count(),
                'registered' => $g->filter($seat)->count(),
                'conversion' => $this->pct($g->filter($seat)->count(), $g->count()),
            ])->sortByDesc('interested')->values();

        // حسب الحالة
        $statuses = collect(Registration::STATUSES)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $regs->where('status', $key)->count()])
            ->filter(fn ($s) => $s['count'] > 0)->values();

        // الدورات: التي تبدأ في الفترة أو لها طلبات في الفترة
        $programIds = $regs->pluck('program_id')->filter()->unique();
        $programs = Program::query()
            ->where(fn ($q) => $q->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->orWhereIn('id', $programIds))
            ->withCount([
                'registrations',
                'seatRegistrations',
                'registrations as interested_in_period' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
            ])
            ->orderBy('start_date')
            ->get();

        $att = ProgramAttendance::whereIn('program_id', $programs->pluck('id'))
            ->selectRaw('program_id, status, count(*) as n')
            ->groupBy('program_id', 'status')
            ->get()->groupBy('program_id');

        $courseRows = $programs->map(function (Program $p) use ($att) {
            $a = $att->get($p->id, collect());
            $marked = (int) $a->sum('n');
            $attended = (int) $a->whereIn('status', ProgramAttendance::ATTENDED)->sum('n');

            return [
                'program' => $p,
                'interested' => $p->registrations_count,
                'registered' => $p->seat_registrations_count,
                'conversion' => $this->pct($p->seat_registrations_count, $p->registrations_count),
                'fill' => $p->seats ? $this->pct($p->seat_registrations_count, $p->seats) : null,
                'attendance' => $marked ? $this->pct($attended, $marked) : null,
                'revenue' => $p->seat_registrations_count * $this->priceOf($p),
            ];
        });

        $attAll = $att->flatten(1);
        $kpi['attendance'] = ($m = (int) $attAll->sum('n'))
            ? $this->pct((int) $attAll->whereIn('status', ProgramAttendance::ATTENDED)->sum('n'), $m)
            : null;

        // آخر 6 أشهر
        $start = now()->startOfMonth()->subMonths(5);
        $recent = Registration::where('created_at', '>=', $start)->get(['status', 'created_at']);
        $months = collect(range(0, 5))->map(function ($i) use ($start, $recent, $seat) {
            $m = $start->copy()->addMonths($i);
            $g = $recent->filter(fn ($r) => $r->created_at->format('Y-m') === $m->format('Y-m'));

            return [
                'label' => $m->translatedFormat('F Y'),
                'interested' => $g->count(),
                'registered' => $g->filter($seat)->count(),
            ];
        });
        $maxMonth = max(1, $months->max('interested'));

        return view('admin.reports.index', compact(
            'period', 'from', 'to', 'kpi', 'sources', 'statuses', 'courseRows', 'months', 'maxMonth'
        ));
    }

    /** تصدير ملخص الدورات CSV (يفتح في Excel) */
    public function export(Request $request)
    {
        [, $from, $to] = $this->range($request);
        $view = $this->index($request);
        $rows = $view->getData()['courseRows'];

        $name = 'تقرير-الدورات-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الدورة', 'تاريخ البدء', 'المقاعد', 'المهتمون', 'المسجّلون', 'نسبة التحويل %', 'نسبة الامتلاء %', 'نسبة الحضور %', 'الإيراد المتوقع']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['program']->title,
                    $r['program']->start_date?->format('Y-m-d'),
                    $r['program']->seats,
                    $r['interested'],
                    $r['registered'],
                    $r['conversion'],
                    $r['fill'],
                    $r['attendance'],
                    $r['revenue'],
                ]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:string,1:Carbon,2:Carbon} */
    protected function range(Request $request): array
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'quarter';

        if ($period === 'custom') {
            try {
                $from = Carbon::parse($request->query('from'))->startOfDay();
                $to = Carbon::parse($request->query('to'))->endOfDay();
            } catch (\Throwable) {
                $period = 'quarter';
            }
            if (isset($from, $to) && $from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
        }

        return match ($period) {
            'month' => [$period, now()->startOfMonth(), now()->endOfDay()],
            'last_month' => [$period, now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'year' => [$period, now()->startOfYear(), now()->endOfDay()],
            'custom' => [$period, $from, $to],
            default => ['quarter', now()->subMonths(3)->startOfDay(), now()->endOfDay()],
        };
    }

    /** السعر الفعلي للمقعد: سعر العرض إن وُجد وإلا السعر */
    protected function priceOf(?Program $p): float
    {
        if (! $p) {
            return 0;
        }

        return (float) ($p->sale_price !== null && (float) $p->sale_price < (float) $p->price ? $p->sale_price : $p->price);
    }

    protected function pct(int|float $part, int|float $whole): int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : 0;
    }
}
