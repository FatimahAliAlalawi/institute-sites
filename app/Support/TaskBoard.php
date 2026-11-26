<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Program;
use App\Models\ProgramAttendance;
use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * مسؤوليات كل موظف: خطوات «آلية التسجيل» المسندة له في كل دورة (من أسماء المسؤولين في «تشغيل الدورة»)،
 * وتحضير دوراته إن كان مدربًا. الإدارة ترى العملية كاملة: وين وصلت عند كل موظف.
 */
class TaskBoard
{
    /** دورات جارية أو قادمة (من أسبوعين مضت فأكثر) أو بلا تاريخ وفيها متدربون */
    public static function activePrograms(): Collection
    {
        $from = now()->subDays(14)->toDateString();

        return Program::with(['seatRegistrations'])
            ->where(fn ($q) => $q->whereDate('start_date', '>=', $from)->orWhere(fn ($w) => $w->whereNull('start_date')->whereHas('seatRegistrations')))
            ->orderByRaw('start_date is null')->orderBy('start_date')
            ->get();
    }

    /** هل نص المسؤولين («ابتسام، باسل») يشمل هذا الموظف؟ المطابقة بالاسم الكامل أو الاسم الأول */
    public static function matches(Admin $admin, string $assignees): bool
    {
        $clean = fn ($s) => trim(preg_replace('/^(أ\.?|ا\.|الأستاذة?|م\.)\s*/u', '', trim($s)));
        $full = $clean((string) $admin->name);
        if ($full === '') {
            return false;
        }
        $first = explode(' ', $full)[0];
        foreach (preg_split('/\s*[،,\/]\s*|\s+و\s+/u', $assignees) as $name) {
            $name = $clean($name);
            if ($name !== '' && ($name === $full || $name === $first || str_starts_with($full, $name.' ') || str_starts_with($name, $full))) {
                return true;
            }
        }

        return false;
    }

    /** مهام موظف واحد */
    public static function forAdmin(Admin $admin, ?Collection $programs = null): Collection
    {
        $programs ??= static::activePrograms();
        $today = now()->startOfDay();
        $tasks = collect();

        foreach ($programs as $p) {
            $trainees = $p->seatRegistrations;
            foreach (Program::OPS_STEPS as $key => [$title, $detail, $kind]) {
                if ($key === 'register' || ! static::matches($admin, $p->opsAssignees($key))) {
                    continue;
                }
                $due = $p->opsDue($key);
                if ($kind === 'course') {
                    $done = (bool) $p->opsCourseDone($key);
                    $total = 1;
                    $pending = $done ? 0 : 1;
                } else {
                    $total = $trainees->count();
                    $pending = $trainees->filter(fn (Registration $t) => ! $t->stepDone($key))->count();
                    $done = $total > 0 && $pending === 0;
                }
                if ($total === 0) {
                    continue;
                }
                $tasks->push([
                    'program' => $p, 'key' => $key, 'title' => $title, 'detail' => $detail, 'kind' => $kind,
                    'due' => $due, 'total' => $total, 'pending' => $pending, 'done' => $done,
                    'overdue' => ! $done && $due && $due->lt($today),
                    'today' => ! $done && $due && $due->isSameDay($today),
                    'link' => route('admin.programs.ops', $p),
                ]);
            }

            // متابعة الغياب: لمسؤول «جدول التحضير» — متدربون على حد الغياب أو تجاوزوه
            if ($trainees->isNotEmpty() && static::matches($admin, $p->opsAssignees('prep_sheet'))) {
                $flags = $trainees->map(fn (Registration $t) => ProgramAttendance::summaryFor($t, $p)['cert'])->countBy();
                $over = $flags->get('over', 0);
                $near = $flags->get('near', 0);
                if ($over + $near > 0) {
                    $tasks->push([
                        'program' => $p, 'key' => 'absence', 'title' => 'متابعة الغياب والشهادة',
                        'detail' => trim(($over ? $over.' تجاوز الحد (لا يستحق الشهادة)' : '').($over && $near ? '، ' : '').($near ? $near.' على حد الغياب' : '')).' — بلّغهم برسالة تقرير الحضور',
                        'kind' => 'course', 'due' => $today->copy(), 'total' => $over + $near, 'pending' => $over + $near, 'done' => false,
                        'overdue' => false, 'today' => true,
                        'link' => route('admin.course-attendance.show', $p),
                    ]);
                }
            }

            // المدرب: أسئلة الاختبار، ثم القبلي يوم البدء والبعدي في آخر يوم
            if ($p->trainer_admin_id === $admin->id && $p->start_date && ! $p->plan_exec && $trainees->isNotEmpty()) {
                $first = $p->session_dates ? \Illuminate\Support\Carbon::parse($p->session_dates[0]) : $p->start_date->copy();
                $last = $p->session_dates ? \Illuminate\Support\Carbon::parse(\Illuminate\Support\Arr::last($p->session_dates)) : $first->copy();
                $ids = $trainees->pluck('id');
                $taken = \App\Models\ProgramTestResult::where('program_id', $p->id)->whereIn('registration_id', $ids)->get()->countBy('kind');
                foreach ([
                    ['test-questions', 'إضافة أسئلة الاختبار القبلي والبعدي', 'قبل الدورة بثلاثة أيام', $first->copy()->subDays(3), 1, $p->questions->isNotEmpty() ? 0 : 1],
                    ['test-pre', 'الاختبار القبلي', 'افتحه وأرسل الروابط للمتدربين', $first, $ids->count(), $ids->count() - $taken->get('pre', 0)],
                    ['test-post', 'الاختبار البعدي', 'في آخر يوم من الدورة', $last, $ids->count(), $ids->count() - $taken->get('post', 0)],
                ] as [$key, $title, $detail, $due, $total, $pending]) {
                    $tasks->push([
                        'program' => $p, 'key' => $key, 'title' => $title, 'detail' => $detail,
                        'kind' => $key === 'test-questions' ? 'course' : 'trainee', 'due' => $due,
                        'total' => $total, 'pending' => $pending, 'done' => $pending === 0,
                        'overdue' => $pending > 0 && $due->lt($today), 'today' => $pending > 0 && $due->isSameDay($today),
                        'link' => route('admin.course-attendance.tests', $p),
                    ]);
                }
            }

            // المدرب: الخطة والتقرير لكل أسبوع، وتقرير الاختبار القبلي والبعدي
            if ($p->trainer_admin_id === $admin->id && ! $p->plan_exec) {
                foreach (static::fileTasks($p, $today) as $t) {
                    $tasks->push($t);
                }
            }

            // المدرب: تحضير أيام دورته
            if ($p->trainer_admin_id === $admin->id) {
                $recorded = ProgramAttendance::where('program_id', $p->id)->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))->unique()->all();
                foreach ($p->session_dates as $d) {
                    if ($d > $today->toDateString()) {
                        continue;
                    }
                    $done = in_array($d, $recorded, true);
                    $tasks->push([
                        'program' => $p, 'key' => 'attendance-'.$d, 'title' => 'تحضير يوم '.\Illuminate\Support\Carbon::parse($d)->translatedFormat('l j F'),
                        'detail' => 'تسجيل حضور المتدربين', 'kind' => 'course', 'due' => \Illuminate\Support\Carbon::parse($d),
                        'total' => 1, 'pending' => $done ? 0 : 1, 'done' => $done,
                        'overdue' => ! $done && $d < $today->toDateString(), 'today' => ! $done && $d === $today->toDateString(),
                        'link' => route('admin.course-attendance.show', [$p, 'date' => $d]),
                    ]);
                }
            }
        }

        return $tasks->sortBy(fn ($t) => [$t['done'] ? 1 : 0, $t['overdue'] ? 0 : 1, $t['due']?->timestamp ?? PHP_INT_MAX])->values();
    }

    /** ملفات المدرب المطلوبة للدورة: تظهر الخطة قبل أسبوعها بثلاثة أيام، والتقارير عند حلول موعدها */
    public static function fileTasks(Program $p, \Illuminate\Support\Carbon $today): array
    {
        $weeks = $p->sessionWeeks();
        if (! $weeks) {
            return [];
        }
        $have = $p->reports()->get(['kind', 'week'])->map(fn ($r) => $r->kind.'|'.$r->week?->toDateString())->flip();
        $all = array_merge(...array_values($weeks));
        $first = \Illuminate\Support\Carbon::parse($all[0]);
        $last = \Illuminate\Support\Carbon::parse(end($all));
        $items = [];
        $n = 0;
        foreach ($weeks as $week => $days) {
            $n++;
            $label = count($weeks) > 1 ? ' — الأسبوع '.$n : '';
            $items[] = ['weekly_plan', $week, 'الخطة الأسبوعية'.$label, 'ارفع خطة الأسبوع قبل بدايته', \Illuminate\Support\Carbon::parse($days[0]), -3];
            $items[] = ['weekly_report', $week, 'التقرير الأسبوعي'.$label, 'ارفع تقرير الأسبوع بعد آخر يوم فيه', \Illuminate\Support\Carbon::parse(end($days))->addDay(), 0];
        }
        $items[] = ['pre_report', null, 'رفع تقرير الاختبار القبلي', 'بعد ما يؤدي المتدربون الاختبار القبلي', $first->copy()->addDay(), -1];
        $items[] = ['post_report', null, 'رفع تقرير الاختبار البعدي', 'بعد ما يؤدي المتدربون الاختبار البعدي', $last->copy()->addDay(), -1];

        $out = [];
        foreach ($items as [$kind, $week, $title, $detail, $due, $showBefore]) {
            if ($due->copy()->addDays($showBefore)->gt($today)) {
                continue;
            }
            $done = isset($have[$kind.'|'.$week]);
            $out[] = [
                'program' => $p, 'key' => 'file-'.$kind.'-'.$week, 'title' => $title, 'detail' => $detail,
                'kind' => 'course', 'due' => $due, 'total' => 1, 'pending' => $done ? 0 : 1, 'done' => $done,
                'overdue' => ! $done && $due->lt($today), 'today' => ! $done && $due->isSameDay($today),
                'link' => route('admin.course-attendance.files', [$p, 'kind' => $kind, 'week' => $week]),
            ];
        }

        return $out;
    }

    /** للوحة الإدارة: كل موظف ومهامه */
    public static function team(): Collection
    {
        $programs = static::activePrograms();

        return Admin::with('role')->where('is_active', true)->orderBy('name')->get()
            ->map(fn (Admin $a) => ['admin' => $a, 'tasks' => static::forAdmin($a, $programs)])
            ->filter(fn ($row) => $row['tasks']->isNotEmpty())
            ->values();
    }
}
