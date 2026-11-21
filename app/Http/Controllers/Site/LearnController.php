<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\LiveAttendance;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Support\DailyRelease;
use App\Support\Uploader;
use Illuminate\Http\Request;

/** مشغّل الدورة: الدروس، إكمال الدرس، المرفقات، الاختبارات (مع نظام "درس يوميًا" للدورات المسجلة) */
class LearnController extends Controller
{
    public function start(Request $request, Course $course)
    {
        $this->ensureEnrolled($request, $course);
        $next = $course->nextItemFor($request->user());

        if (! $next) {
            return redirect()->route('courses.show', $course)->with('status', 'لم يُضف محتوى لهذه الدورة بعد.');
        }

        return redirect()->route(Course::itemRoute($next['type']), [$course, $next['model']]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id == $course->id && $course->is_published, 404);
        $user = $request->user();
        $enrolled = $user?->isEnrolled($course) ?? false;

        // الدروس المجانية (معاينة) متاحة للجميع، وغيرها للمشتركين فقط
        if (! $enrolled && ! $lesson->is_preview) {
            return $user
                ? redirect()->route('courses.show', $course)->with('status', 'اشترك في الدورة لمشاهدة هذا الدرس.')
                : redirect()->guest(route('login'));
        }

        $data = $this->playerData($course, $user, 'lesson', $lesson);

        return view('site.learn.lesson', $data + [
            'lesson' => $lesson,
            'enrolled' => $enrolled,
            'locked' => $enrolled && ! DailyRelease::isUnlocked($data['release'], 'lesson', $lesson->id),
            'isDone' => $user && LessonCompletion::where('user_id', $user->id)->where('lesson_id', $lesson->id)->exists(),
        ]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id == $course->id, 404);
        $this->ensureEnrolled($request, $course);
        $user = $request->user();

        $curriculum = $course->curriculum();
        $state = DailyRelease::state($course, $user, $course->flatItems($curriculum));
        if (! DailyRelease::isUnlocked($state, 'lesson', $lesson->id)) {
            return redirect()->route('learn.lesson', [$course, $lesson]);
        }

        LessonCompletion::firstOrCreate(['user_id' => $user->id, 'lesson_id' => $lesson->id], ['course_id' => $course->id]);
        $certificate = $course->syncCompletion($user);

        if ($certificate && $certificate->wasRecentlyCreated) {
            return redirect()->route('certificates.show', $certificate->code)->with('status', '🎉 مبروك! أكملت الدورة وحصلت على الشهادة.');
        }

        $next = $this->neighbours($course, 'lesson', $lesson, $curriculum)['next'];
        if ($next && $state['enabled']) {
            $after = DailyRelease::state($course, $user, $course->flatItems($curriculum));
            if (! DailyRelease::isUnlocked($after, $next['type'], $next['model']->id)) {
                return redirect()->route('learn.lesson', [$course, $lesson])->with('status',
                    '✅ أحسنت! تم تسجيل حضورك لدرس اليوم. الدرس التالي يُتاح '.DailyRelease::humanDay($after['next_day']).'.');
            }
        }

        return $next
            ? redirect()->route(Course::itemRoute($next['type']), [$course, $next['model']])
            : redirect()->route('learn.lesson', [$course, $lesson])->with('status', 'تم تسجيل إكمال الدرس.');
    }

    /** صفحة المحاضرة المباشرة */
    public function live(Request $request, Course $course, LiveSession $session)
    {
        abort_unless($session->course_id == $course->id, 404);
        $this->ensureEnrolled($request, $course);

        return view('site.learn.live', $this->playerData($course, $request->user(), 'live', $session) + [
            'session' => $session,
            'attended' => LiveAttendance::where('live_session_id', $session->id)->where('user_id', $request->user()->id)->exists(),
        ]);
    }

    /** الانضمام: تسجيل الحضور ثم التحويل لرابط Zoom */
    public function joinLive(Request $request, Course $course, LiveSession $session)
    {
        abort_unless($session->course_id == $course->id, 404);
        $this->ensureEnrolled($request, $course);
        if ($session->state !== 'open' || ! $session->join_url) {
            return redirect()->route('learn.live', [$course, $session])->with('status', $session->state === 'ended' ? 'انتهت المحاضرة.' : 'زر الانضمام يُفتح قبل الموعد بـ '.LiveSession::OPEN_BEFORE_MINUTES.' دقيقة.');
        }

        $attendance = LiveAttendance::firstOrNew(['live_session_id' => $session->id, 'user_id' => $request->user()->id]);
        $attendance->joined_at ??= now();
        $attendance->join_count = $attendance->exists ? $attendance->join_count + 1 : 1;
        $attendance->save();

        return redirect()->away($session->join_url);
    }

    public function attachment(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id == $course->id && $lesson->attachment, 404);
        $user = $request->user();
        abort_unless($lesson->is_preview || ($user?->isEnrolled($course)
            && DailyRelease::isUnlocked(DailyRelease::state($course, $user), 'lesson', $lesson->id)), 403);
        $path = Uploader::privatePath($lesson->attachment);
        abort_unless($path, 404);

        return response()->download($path, $lesson->attachment_name ?: basename($path));
    }

    public function quiz(Request $request, Course $course, Quiz $quiz)
    {
        abort_unless($quiz->course_id == $course->id, 404);
        $this->ensureEnrolled($request, $course);
        $user = $request->user();
        $data = $this->playerData($course, $user, 'quiz', $quiz);

        return view('site.learn.quiz', $data + [
            'quiz' => $quiz->load('questions'),
            'attempts' => $quiz->attempts()->where('user_id', $user->id)->latest()->get(),
            'attemptsLeft' => $quiz->attemptsLeftFor($user),
            'started' => $request->boolean('start'),
            'locked' => ! DailyRelease::isUnlocked($data['release'], 'quiz', $quiz->id),
        ]);
    }

    public function submitQuiz(Request $request, Course $course, Quiz $quiz)
    {
        abort_unless($quiz->course_id == $course->id, 404);
        $this->ensureEnrolled($request, $course);
        $user = $request->user();

        if (! DailyRelease::isUnlocked(DailyRelease::state($course, $user), 'quiz', $quiz->id)) {
            return redirect()->route('learn.quiz', [$course, $quiz]);
        }
        if ($quiz->attemptsLeftFor($user) === 0) {
            return redirect()->route('learn.quiz', [$course, $quiz])->with('status', 'استنفدت عدد المحاولات المسموح.');
        }

        $result = $quiz->load('questions')->grade((array) $request->input('answers', []));
        $attempt = QuizAttempt::create($result + ['user_id' => $user->id, 'quiz_id' => $quiz->id, 'course_id' => $course->id]);

        $certificate = $result['passed'] ? $course->syncCompletion($user) : null;
        if ($certificate && $certificate->wasRecentlyCreated) {
            session()->flash('certificate_code', $certificate->code);
        }

        return redirect()->route('learn.attempt', [$course, $attempt]);
    }

    public function attempt(Request $request, Course $course, QuizAttempt $attempt)
    {
        abort_unless($attempt->course_id == $course->id && $attempt->user_id == $request->user()->id, 404);
        $quiz = $attempt->quiz->load('questions');

        return view('site.learn.result', $this->playerData($course, $request->user(), 'quiz', $quiz) + [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'attemptsLeft' => $quiz->attemptsLeftFor($request->user()),
        ]);
    }

    // ===== مساعدات =====

    protected function ensureEnrolled(Request $request, Course $course): void
    {
        abort_unless($course->is_published || $request->user()?->isEnrolled($course), 404);
        if (! $request->user()?->isEnrolled($course)) {
            abort(redirect()->route('courses.show', $course)->with('status', 'يجب الاشتراك في الدورة أولًا.'));
        }
    }

    protected function playerData(Course $course, $user, string $type, $current): array
    {
        $curriculum = $course->curriculum();
        $items = $course->flatItems($curriculum);
        $progress = $course->progressFor($user);

        return [
            'course' => $course,
            'curriculum' => $curriculum,
            'progress' => $progress,
            'release' => DailyRelease::state($course, $user, $items, $progress),
            'currentType' => $type,
            'currentId' => $current->id,
            'certificate' => $user ? $user->certificates()->where('course_id', $course->id)->first() : null,
        ] + $this->neighbours($course, $type, $current, $curriculum);
    }

    /** العنصر السابق والتالي في المنهج */
    protected function neighbours(Course $course, string $type, $current, $curriculum = null): array
    {
        $items = $course->flatItems($curriculum);
        $index = $items->search(fn ($item) => $item['type'] === $type && $item['model']->id == $current->id);

        return [
            'prev' => $index !== false && $index > 0 ? $items[$index - 1] : null,
            'next' => $index !== false ? ($items[$index + 1] ?? null) : null,
        ];
    }
}
