<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Program;
use App\Models\ProgramAttendance;
use App\Support\Ics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * تحضير الدورات الحضورية + تقويم أيامها.
 * المدرب (صلاحية التحضير فقط) يرى الدورات المربوطة بحسابه فقط، والإدارة ترى الكل.
 */
class CourseAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $admin = $this->admin($request);
        $programs = $this->visible($admin)->withCount('seatRegistrations')
            ->orderByRaw('start_date is null')->orderByDesc('start_date')->get();

        $month = $this->month($request->query('month'));
        $recorded = ProgramAttendance::whereIn('program_id', $programs->pluck('id'))
            ->select('program_id', 'date')->distinct()->get()
            ->map(fn ($r) => $r->program_id.'|'.Carbon::parse($r->date)->toDateString())->flip();

        // أيام الشهر وما فيها من جلسات
        $days = [];
        foreach ($programs as $p) {
            foreach ($p->session_dates ?: ($p->start_date ? [$p->start_date->toDateString()] : []) as $d) {
                if (str_starts_with($d, $month->format('Y-m'))) {
                    $days[$d][] = ['program' => $p, 'done' => isset($recorded[$p->id.'|'.$d])];
                }
            }
        }

        $today = ProgramAttendance::whereDate('date', now()->toDateString())->distinct()->pluck('program_id')->all();

        return view('admin.course-attendance.index', [
            'programs' => $programs, 'today' => $today, 'month' => $month, 'days' => $days,
            'trainerOnly' => $admin?->isTrainerOnly() ?? false,
        ]);
    }

    public function show(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);

        $sessions = $program->session_dates;
        $date = $request->query('date');
        if (! $date) {
            // اليوم إن كان من أيام الدورة، وإلا أقرب يوم سابق، وإلا أول يوم
            $todayStr = now()->toDateString();
            $past = array_values(array_filter($sessions, fn ($d) => $d <= $todayStr));
            $date = in_array($todayStr, $sessions, true) || ! $sessions ? $todayStr : (end($past) ?: $sessions[0]);
        }
        $date = $this->date($date);

        $trainees = $program->seatRegistrations()->orderBy('name')->get();
        $records = ProgramAttendance::where('program_id', $program->id)->whereDate('date', $date)->get()->keyBy('registration_id');
        $recordedDates = ProgramAttendance::where('program_id', $program->id)->select('date')->distinct()->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->values()->all();
        $dates = collect(array_unique(array_merge($sessions, $recordedDates)))->sort()->values();

        $summary = $trainees->mapWithKeys(fn ($t) => [$t->id => ProgramAttendance::summaryFor($t)]);

        return view('admin.course-attendance.show', compact('program', 'date', 'trainees', 'records', 'dates', 'summary', 'sessions', 'recordedDates'));
    }

    public function store(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);

        $data = $request->validate([
            'date' => 'required|date',
            'status' => 'array',
            'status.*' => ['nullable', Rule::in(array_keys(ProgramAttendance::STATUSES))],
            'note' => 'array',
            'note.*' => 'nullable|string|max:255',
        ]);
        $date = $this->date($data['date']);
        $admin = $this->admin($request);
        $by = $admin?->name ?: $admin?->username;

        $saved = 0;
        foreach ($program->seatRegistrations()->pluck('id') as $id) {
            $status = $data['status'][$id] ?? null;
            if (! $status) {
                ProgramAttendance::where('registration_id', $id)->whereDate('date', $date)->delete();
                continue;
            }
            // البحث بـ whereDate حتى يتطابق اليوم أيًا كانت صيغة تخزين التاريخ في قاعدة البيانات
            $row = ProgramAttendance::where('registration_id', $id)->whereDate('date', $date)->first()
                ?? new ProgramAttendance(['registration_id' => $id, 'date' => $date]);
            $row->fill(['program_id' => $program->id, 'status' => $status, 'note' => $data['note'][$id] ?? null, 'recorded_by' => $by])->save();
            $saved++;
        }

        return redirect()->route('admin.course-attendance.show', [$program, 'date' => $date])
            ->with('status', 'تم حفظ تحضير '.$saved.' متدرب ليوم '.$date.'.');
    }

    /** ملف تقويم بكل جلسات دورات المدرب (أو دورة واحدة) لإضافتها لتقويم الجوال */
    public function ics(Request $request)
    {
        $admin = $this->admin($request);
        $query = $this->visible($admin);
        if ($request->filled('program')) {
            $query->whereKey($request->integer('program'));
        }
        $name = $admin?->isTrainerOnly() ? 'دوراتي — '.$admin->name : 'دورات '.setting('site_name');

        return Ics::response(Ics::build($query->get(), $name), 'courses.ics');
    }

    protected function admin(Request $request): ?Admin
    {
        return Admin::with('role')->find($request->session()->get('admin_id'));
    }

    protected function visible(?Admin $admin): Builder
    {
        return Program::query()
            ->when($admin?->isTrainerOnly(), fn ($q) => $q->where('trainer_admin_id', $admin->id))
            ->when(! $admin?->isTrainerOnly(), fn ($q) => $q->where(fn ($w) => $w->whereHas('seatRegistrations')->orWhereNotNull('sessions')));
    }

    protected function authorizeProgram(Request $request, Program $program): void
    {
        $admin = $this->admin($request);
        abort_if($admin?->isTrainerOnly() && $program->trainer_admin_id !== $admin->id, 403, 'هذه الدورة ليست ضمن دوراتك.');
    }

    protected function date(?string $value): string
    {
        try {
            return $value ? Carbon::parse($value)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    protected function month(?string $value): Carbon
    {
        return preg_match('/^\d{4}-\d{2}$/', (string) $value) ? Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfMonth() : now()->startOfMonth();
    }
}
