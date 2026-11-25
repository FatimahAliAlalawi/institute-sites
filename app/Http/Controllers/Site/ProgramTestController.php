<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ProgramTestResult;
use App\Models\Registration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

/** صفحة المتدرب للاختبار القبلي/البعدي برابط شخصي سري (بدون حساب) */
class ProgramTestController extends Controller
{
    public function show(string $token, string $kind)
    {
        [$registration, $program] = $this->resolve($token);

        $results = ProgramTestResult::where('registration_id', $registration->id)->where('program_id', $program->id)->get()->keyBy('kind');

        return response()->view('site.program-test', [
            'registration' => $registration,
            'program' => $program,
            'kind' => $kind,
            'token' => $token,
            'result' => $results->get($kind),
            'preResult' => $results->get('pre'),
            'open' => $program->testOpen($kind),
            'questions' => $program->questions,
        ])->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'same-origin');
    }

    public function store(Request $request, string $token, string $kind)
    {
        [$registration, $program] = $this->resolve($token);
        $back = redirect()->route('program-test', [$token, $kind]);

        if (ProgramTestResult::where('registration_id', $registration->id)->where('kind', $kind)->exists()) {
            return $back;
        }
        if (! $program->testOpen($kind)) {
            return $back->withErrors(['answers' => 'الاختبار مغلق حاليًا.']);
        }

        $request->validate(['answers' => 'array', 'answers.*' => 'nullable|integer|min:0|max:20']);
        $questions = $program->questions;
        $given = (array) $request->input('answers', []);

        $missing = $questions->filter(fn ($q) => ! isset($given[$q->id]) || ! array_key_exists((int) $given[$q->id], $q->options));
        if ($missing->isNotEmpty()) {
            return $back->withInput()->withErrors(['answers' => 'باقي '.$missing->count().' سؤال بدون إجابة.']);
        }

        $answers = [];
        $earned = 0;
        foreach ($questions as $q) {
            $answers[$q->id] = (int) $given[$q->id];
            $earned += $answers[$q->id] === $q->correct ? 1 : 0;
        }

        try {
            ProgramTestResult::create([
                'program_id' => $program->id,
                'registration_id' => $registration->id,
                'kind' => $kind,
                'score' => (int) round($earned * 100 / $questions->count()),
                'earned' => $earned,
                'total' => $questions->count(),
                'answers' => $answers,
            ]);
        } catch (UniqueConstraintViolationException) {
            // إرسال مكرر في نفس اللحظة: تبقى النتيجة الأولى
        }

        return $back;
    }

    protected function resolve(string $token): array
    {
        $registration = Registration::with('program.questions')->where('test_token', $token)
            ->whereIn('status', Registration::SEAT_STATUSES)->first();
        abort_unless($registration?->program, 404);

        return [$registration, $registration->program];
    }
}
