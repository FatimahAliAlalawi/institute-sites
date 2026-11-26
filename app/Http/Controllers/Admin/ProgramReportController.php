<?php

namespace App\Http\Controllers\Admin;

use App\Models\Program;
use App\Models\ProgramReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** الخطط والتقارير التي يرفعها المدرب لدورته (ملفات خاصة لا تُفتح إلا من لوحة التحكم) */
class ProgramReportController extends CourseAttendanceController
{
    public function files(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        $weeks = $program->sessionWeeks();

        return view('admin.course-attendance.files', [
            'program' => $program,
            'weeks' => $weeks,
            'reports' => $program->reports()->get(),
            'kind' => array_key_exists($request->query('kind'), ProgramReport::KINDS) ? $request->query('kind') : 'weekly_plan',
            'week' => in_array($request->query('week'), array_keys($weeks), true) ? $request->query('week') : $this->currentWeek($weeks),
            'admin' => $this->admin($request),
        ]);
    }

    public function upload(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        $weeks = array_keys($program->sessionWeeks());
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ProgramReport::KINDS))],
            'week' => ['nullable', Rule::in($weeks)],
            'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt',
            'note' => 'nullable|string|max:5000',
        ], [
            'file.mimes' => 'الملف لازم يكون PDF أو Word أو Excel أو PowerPoint أو صورة.',
            'file.max' => 'حجم الملف أكبر من 20 ميجا.',
            'week.in' => 'اختر أسبوعًا من أسابيع الدورة.',
        ]);
        if (! $request->hasFile('file') && trim((string) ($data['note'] ?? '')) === '') {
            return back()->withInput()->withErrors(['file' => 'ارفع ملفًا أو اكتب التقرير في المربع.']);
        }
        $weekly = ProgramReport::KINDS[$data['kind']][1];
        if ($weekly && empty($data['week'])) {
            return back()->withInput()->withErrors(['week' => 'اختر الأسبوع.']);
        }

        $admin = $this->admin($request);
        $file = $request->file('file');
        ProgramReport::create([
            'program_id' => $program->id,
            'kind' => $data['kind'],
            'week' => $weekly ? $data['week'] : null,
            'path' => $file?->store('program-reports/'.$program->id, 'local'),
            'original_name' => $file ? mb_substr($file->getClientOriginalName(), 0, 200) : null,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name,
        ]);

        return redirect()->route('admin.course-attendance.files', $program)->with('status', 'تم رفع '.ProgramReport::KINDS[$data['kind']][0].'.');
    }

    public function download(Request $request, Program $program, ProgramReport $report)
    {
        $this->authorizeProgram($request, $program);
        abort_unless($report->program_id === $program->id && $report->path && Storage::disk('local')->exists($report->path), 404);

        return Storage::disk('local')->download($report->path, $report->original_name ?: basename($report->path));
    }

    public function destroy(Request $request, Program $program, ProgramReport $report)
    {
        $this->authorizeProgram($request, $program);
        $admin = $this->admin($request);
        abort_unless($report->program_id === $program->id, 404);
        abort_if($admin?->isTrainerOnly() && $report->admin_id !== $admin->id, 403);
        if ($report->path) {
            Storage::disk('local')->delete($report->path);
        }
        $report->delete();

        return back()->with('status', 'تم الحذف.');
    }

    protected function currentWeek(array $weeks): ?string
    {
        $now = now()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY)->toDateString();
        $keys = array_keys($weeks);
        $past = array_filter($keys, fn ($w) => $w <= $now);

        return $past ? end($past) : ($keys[0] ?? null);
    }
}
