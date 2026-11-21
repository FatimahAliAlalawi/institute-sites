<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Support\DailyRelease;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** تحضير الدورات المسجلة: حضور/غياب كل متدرب يوميًا (الحضور = إكمال درس في ذلك اليوم) */
class AttendanceController extends Controller
{
    public const LABELS = ['present' => 'حاضر', 'absent' => 'غائب', 'off' => 'إجازة', 'today' => 'لم يحضر بعد', 'none' => '—'];

    public function index(Request $request)
    {
        $courses = Course::where('daily_release', true)->orderBy('title')->get(['id', 'title', 'lessons_per_day']);
        $course = $courses->firstWhere('id', (int) $request->input('course')) ?? $courses->first();

        $to = $this->date($request->input('to')) ?? now()->startOfDay();
        $from = $this->date($request->input('from')) ?? $to->copy()->subDays(6);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 62) {
            $from = $to->copy()->subDays(62);
        }

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dates[] = $d->copy();
        }

        $rows = collect();
        if ($course) {
            $rows = $course->enrollments()->where('status', 'active')->with('user')->get()
                ->filter(fn ($e) => $e->user)
                ->sortBy(fn ($e) => $e->user->name)
                ->map(function ($e) use ($from, $to) {
                    $records = DailyRelease::attendance($e, $from, $to);

                    return [
                        'enrollment' => $e,
                        'user' => $e->user,
                        'records' => $records,
                        'present' => count(array_keys($records, 'present', true)),
                        'absent' => count(array_keys($records, 'absent', true)),
                        'days' => DailyRelease::daysFor($e->user),
                        'progress' => $e->course->progressFor($e->user)['percent'],
                    ];
                })->values();
        }

        if ($request->boolean('export') && $course) {
            return $this->export($course, $dates, $rows);
        }

        return view('admin.attendance.index', compact('courses', 'course', 'from', 'to', 'dates', 'rows'));
    }

    protected function export(Course $course, array $dates, $rows)
    {
        $filename = 'attendance-'.$course->id.'-'.$dates[0]->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($dates, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['المتدرب', 'رقم الهوية', 'الجوال', 'البريد', 'أيام الدراسة'],
                array_map(fn ($d) => DailyRelease::DAYS[$d->dayOfWeek].' '.$d->format('Y-m-d'), $dates),
                ['أيام الحضور', 'أيام الغياب', 'نسبة الحضور', 'التقدم في الدورة']));
            foreach ($rows as $row) {
                $total = $row['present'] + $row['absent'];
                fputcsv($out, array_merge(
                    [$row['user']->name, $row['user']->national_id, $row['user']->phone, $row['user']->email, DailyRelease::label($row['days'])],
                    array_map(fn ($status) => self::LABELS[$status] ?? $status, array_values($row['records'])),
                    [$row['present'], $row['absent'], $total ? round($row['present'] * 100 / $total).'%' : '—', $row['progress'].'%']
                ));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function date($value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
