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
