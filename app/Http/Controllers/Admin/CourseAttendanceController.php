<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Program;
use App\Models\ProgramAttendance;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** تحضير الدورات الحضورية: صلاحية مستقلة حتى يُعطى المدرب التحضير فقط */
class CourseAttendanceController extends Controller
{
    public function index()
    {
        $programs = Program::whereHas('seatRegistrations')
            ->withCount('seatRegistrations')
            ->orderByRaw('start_date is null')->orderByDesc('start_date')
            ->get();

        $today = ProgramAttendance::whereDate('date', now()->toDateString())->distinct()->pluck('program_id')->all();

        return view('admin.course-attendance.index', compact('programs', 'today'));
    }

    public function show(Request $request, Program $program)
    {
        $date = $this->date($request->query('date'));
        $trainees = $program->seatRegistrations()->orderBy('name')->get();
        $records = ProgramAttendance::where('program_id', $program->id)->whereDate('date', $date)->get()->keyBy('registration_id');
        $dates = ProgramAttendance::where('program_id', $program->id)->select('date')->distinct()->orderBy('date')->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->values();

        $summary = $trainees->mapWithKeys(fn ($t) => [$t->id => ProgramAttendance::summaryFor($t)]);

        return view('admin.course-attendance.show', compact('program', 'date', 'trainees', 'records', 'dates', 'summary'));
    }

    public function store(Request $request, Program $program)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'status' => 'array',
            'status.*' => ['nullable', Rule::in(array_keys(ProgramAttendance::STATUSES))],
            'note' => 'array',
            'note.*' => 'nullable|string|max:255',
        ]);
        $date = $this->date($data['date']);
        $admin = Admin::find($request->session()->get('admin_id'));
        $by = $admin?->name ?: $admin?->username;

        $ids = $program->seatRegistrations()->pluck('id')->all();
        $saved = 0;
        foreach ($ids as $id) {
            $status = $data['status'][$id] ?? null;
            if (! $status) {
                ProgramAttendance::where('registration_id', $id)->whereDate('date', $date)->delete();
                continue;
            }
            ProgramAttendance::updateOrCreate(
                ['registration_id' => $id, 'date' => $date],
                ['program_id' => $program->id, 'status' => $status, 'note' => $data['note'][$id] ?? null, 'recorded_by' => $by]
            );
            $saved++;
        }

        return redirect()->route('admin.course-attendance.show', [$program, 'date' => $date])
            ->with('status', 'تم حفظ تحضير '.$saved.' متدرب ليوم '.$date.'.');
    }

    protected function date(?string $value): string
    {
        try {
            return $value ? Carbon::parse($value)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
