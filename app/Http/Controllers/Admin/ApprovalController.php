<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** اعتماد الإدارة للخطة التدريبية ولإعلانات البرامج (الخطوة 0 والخطوة 3 في الدليل التشغيلي للأفراد) */
class ApprovalController extends Controller
{
    public function index()
    {
        $pending = Program::where('plan_status', 'pending')->orWhere('ad_status', 'pending')
            ->orderByRaw('start_date is null')->orderBy('start_date')->get();

        $plan = Program::inPlan()->orderByRaw('start_date is null')->orderBy('start_date')->get();

        return view('admin.approvals.index', compact('pending', 'plan'));
    }

    public function decide(Request $request, Program $program)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['plan', 'ad'])],
            'decision' => ['required', Rule::in(['approved', 'rejected', 'pending', 'none'])],
            'note' => 'nullable|required_if:decision,rejected|string|max:1000',
        ]);

        $admin = Admin::find($request->session()->get('admin_id'));
        $kind = $data['kind'];

        $program->{$kind.'_status'} = $data['decision'];
        $program->{$kind.'_approved_by'} = $data['decision'] === 'approved' ? ($admin?->name ?: $admin?->username) : null;
        $program->{$kind.'_approved_at'} = $data['decision'] === 'approved' ? now() : null;
        // الموعد المخطط: يُثبّت عند أول اعتماد، ويُمسح إذا أُخرجت الدورة من الخطة
        if ($kind === 'plan' && $data['decision'] === 'approved') {
            $program->plan_baseline_date ??= $program->start_date;
        } elseif ($kind === 'plan' && $data['decision'] === 'none') {
            $program->fill(['plan_baseline_date' => null, 'plan_exec' => null, 'plan_exec_note' => null]);
        }
        if ($request->filled('note') || $data['decision'] === 'rejected') {
            $program->approval_note = $data['note'] ?? null;
        }
        $program->save();

        $what = $kind === 'plan' ? 'الخطة' : 'الإعلان';

        return back()->with('status', $what.' — '.$program->title.': '.Program::APPROVAL_STATUSES[$data['decision']].'.');
    }
}
