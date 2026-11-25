<?php

namespace App\Http\Controllers\Admin;

use App\Models\Program;
use App\Models\ProgramTestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** الاختبار القبلي والبعدي للدورة: الأسئلة، الفتح والإغلاق، النتائج (المدرب يرى دوراته فقط) */
class ProgramTestController extends CourseAttendanceController
{
    public function tests(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);

        $trainees = $program->seatRegistrations()->orderBy('name')->get();
        $trainees->each->ensureTestToken();
        $results = $this->results($program, $trainees->pluck('id')->all());

        return view('admin.course-attendance.tests', [
            'program' => $program,
            'trainees' => $trainees,
            'questions' => $program->questions,
            'byTrainee' => $results->groupBy('registration_id')->map->keyBy('kind'),
            'summary' => ProgramTestResult::summarize($results),
            'locked' => $this->locked($program),
            'others' => $this->visible($this->admin($request))->whereKeyNot($program->id)->whereHas('questions')->orderBy('title')->get(['id', 'title']),
        ]);
    }

    /** حفظ كل الأسئلة دفعة واحدة */
    public function saveQuestions(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        if ($this->locked($program)) {
            return back()->withErrors(['q' => 'بدأ المتدربون بالإجابة، فلا تُعدَّل الأسئلة حتى تبقى المقارنة بين القبلي والبعدي صحيحة.']);
        }

        $data = $request->validate([
            'q' => 'array|max:60',
            'q.*.question' => 'nullable|string|max:1000',
            'q.*.options' => 'array|max:6',
            'q.*.options.*' => 'nullable|string|max:500',
            'q.*.correct' => 'nullable|integer|min:0|max:5',
        ]);

        $rows = [];
        $n = 0;
        foreach ($data['q'] ?? [] as $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $filled = array_filter(array_map(fn ($o) => trim((string) $o), $item['options'] ?? []), fn ($o) => $o !== '');
            if ($question === '' && ! $filled) {
                continue;
            }
            $n++;
            $correct = $item['correct'] ?? null;
            if ($question === '' || count($filled) < 2) {
                return back()->withInput()->withErrors(['q' => 'السؤال '.$n.': اكتب نص السؤال وخيارين على الأقل.']);
            }
            if ($correct === null || ! array_key_exists((int) $correct, $filled)) {
                return back()->withInput()->withErrors(['q' => 'السؤال '.$n.': حدّد الإجابة الصحيحة (خيار مكتوب).']);
            }
            $rows[] = [
                'question' => $question,
                'options' => array_values($filled),
                'correct' => array_search((int) $correct, array_keys($filled), true),
                'sort' => $n,
            ];
        }

        DB::transaction(function () use ($program, $rows) {
            $program->questions()->delete();
            $program->questions()->createMany($rows);
            if (! $rows) {
                $program->forceFill(['pre_test_open' => false, 'post_test_open' => false])->save();
            }
        });

        return back()->with('status', 'تم حفظ '.count($rows).' سؤال.');
    }

    /** نسخ أسئلة دورة أخرى (لإعادة نفس الدورة) */
    public function copyQuestions(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        $from = Program::findOrFail($request->validate(['from' => 'required|integer'])['from']);
        $this->authorizeProgram($request, $from);

        if ($this->locked($program) || $program->questions()->exists()) {
            return back()->withErrors(['q' => 'النسخ متاح فقط لدورة بلا أسئلة.']);
        }
        $program->questions()->createMany($from->questions->map(fn ($q) => $q->only(['question', 'options', 'correct', 'sort']))->all());

        return back()->with('status', 'تم نسخ الأسئلة من «'.$from->title.'».');
    }

    public function toggle(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        $data = $request->validate(['kind' => ['required', Rule::in(array_keys(ProgramTestResult::KINDS))], 'open' => 'required|boolean']);

        if ($data['open'] && ! $program->questions()->exists()) {
            return back()->withErrors(['kind' => 'أضف الأسئلة أولًا ثم افتح الاختبار.']);
        }
        $program->forceFill([$data['kind'].'_test_open' => (bool) $data['open']])->save();

        return back()->with('status', ProgramTestResult::KINDS[$data['kind']].': '.($data['open'] ? 'مفتوح الآن.' : 'مغلق.'));
    }

    /** إدخال درجات اختبار ورقي (لا يغيّر نتيجة أُرسلت إلكترونيًا) */
    public function scores(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        $data = $request->validate([
            'score' => 'array',
            'score.*' => 'array',
            'score.*.*' => 'nullable|integer|min:0|max:100',
        ], [], ['score.*.*' => 'الدرجة']);
        $admin = $this->admin($request);

        foreach ($program->seatRegistrations()->pluck('id') as $id) {
            foreach (array_keys(ProgramTestResult::KINDS) as $kind) {
                if (! array_key_exists($kind, $data['score'][$id] ?? [])) {
                    continue;
                }
                $value = $data['score'][$id][$kind];
                $row = ProgramTestResult::where('registration_id', $id)->where('kind', $kind)->first();
                if ($row && $row->answers !== null) {
                    continue;
                }
                if ($value === null) {
                    $row?->delete();
                } elseif (! $row || $row->score !== (int) $value) {
                    ($row ?? new ProgramTestResult(['registration_id' => $id, 'kind' => $kind]))
                        ->fill(['program_id' => $program->id, 'score' => (int) $value, 'recorded_by' => $admin?->name ?: $admin?->username])->save();
                }
            }
        }

        return back()->with('status', 'تم حفظ الدرجات.');
    }

    /** حذف نتيجة متدرب حتى يعيد الاختبار */
    public function reset(Request $request, Program $program, ProgramTestResult $result)
    {
        $this->authorizeProgram($request, $program);
        abort_unless($result->program_id === $program->id, 404);
        $result->delete();

        return back()->with('status', 'تم حذف النتيجة، ويقدر المتدرب يعيد الاختبار.');
    }

    public function exportTests(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program);
        $trainees = $program->seatRegistrations()->orderBy('name')->get();
        $by = $this->results($program, $trainees->pluck('id')->all())->groupBy('registration_id')->map->keyBy('kind');

        return response()->streamDownload(function () use ($trainees, $by) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['المتدرب', 'القبلي %', 'البعدي %', 'الفرق (نقطة)']);
            foreach ($trainees as $t) {
                $pre = $by->get($t->id)?->get('pre')?->score;
                $post = $by->get($t->id)?->get('post')?->score;
                // اسم يبدأ برمز معادلة يُكتب كنص حتى لا ينفّذه Excel
                $name = preg_match('/^[=+\-@\t\r]/', $t->name) ? "'".$t->name : $t->name;
                fputcsv($out, [$name, $pre, $post, $pre !== null && $post !== null ? $post - $pre : null]);
            }
            fclose($out);
        }, 'نتائج-الاختبار-'.$program->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function results(Program $program, array $ids)
    {
        return ProgramTestResult::where('program_id', $program->id)->whereIn('registration_id', $ids)->get();
    }

    /** تُقفل الأسئلة بعد أول إجابة إلكترونية */
    protected function locked(Program $program): bool
    {
        return ProgramTestResult::where('program_id', $program->id)->whereNotNull('answers')->exists();
    }
}
