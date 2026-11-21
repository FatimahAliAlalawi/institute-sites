<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Setting;
use Illuminate\Http\Request;

/** تشغيل الدورة: خطوات «آلية التسجيل» بالمسؤولين والمواعيد، وما نُفّذ لكل متدرب */
class OpsController extends Controller
{
    public function show(Program $program)
    {
        $trainees = $program->registrations()->whereIn('status', Registration::SEAT_STATUSES)->orderBy('name')->get();

        return view('admin.programs.ops', compact('program', 'trainees'));
    }

    public function update(Request $request, Program $program)
    {
        $request->validate([
            'assignees' => 'array',
            'assignees.*' => 'nullable|string|max:190',
            'done' => 'array',
            'steps' => 'array',
            'group_link' => 'nullable|url|max:255',
        ]);

        $admin = Admin::find($request->session()->get('admin_id'));
        $by = $admin?->name ?: $admin?->username;
        $stamp = ['at' => now()->format('Y-m-d H:i'), 'by' => $by];

        $ops = $program->ops ?? [];

        // المسؤولون
        $assignees = [];
        foreach (array_keys(Program::OPS_STEPS) as $step) {
            $assignees[$step] = trim((string) $request->input("assignees.$step", ''));
        }
        $ops['assignees'] = $assignees;
        if ($request->boolean('save_default')) {
            Setting::put(['ops_assignees' => json_encode($assignees, JSON_UNESCAPED_UNICODE)]);
        }

        // خطوات الدورة (مرة واحدة)
        $done = $ops['done'] ?? [];
        foreach (Program::OPS_STEPS as $step => [, , $kind]) {
            if ($kind !== 'course') {
                continue;
            }
            if ($request->boolean("done.$step")) {
                $done[$step] ??= $stamp;
            } else {
                unset($done[$step]);
            }
        }
        $ops['done'] = $done;

        $program->forceFill(['ops' => $ops, 'group_link' => $request->input('group_link') ?: null])->save();

        // خطوات كل متدرب
        $checked = (array) $request->input('steps', []);
        $trainees = $program->registrations()->whereIn('status', Registration::SEAT_STATUSES)->get();
        foreach ($trainees as $t) {
            $steps = $t->steps ?? [];
            foreach (Program::TRAINEE_STEPS as $step) {
                if (! empty($checked[$t->id][$step])) {
                    $steps[$step] ??= $stamp;
                } else {
                    unset($steps[$step]);
                }
            }
            $t->forceFill(['steps' => $steps ?: null])->save();
        }

        return back()->with('status', 'تم حفظ تشغيل الدورة.');
    }
}
