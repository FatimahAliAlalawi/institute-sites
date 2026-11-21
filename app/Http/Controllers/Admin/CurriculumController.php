<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Lesson;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Support\Uploader;
use App\Support\Zoom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** منشئ المنهج: الوحدات ← الدروس والاختبارات ← الأسئلة */
class CurriculumController extends Controller
{
    public function show(Course $course)
    {
        return view('admin.courses.curriculum', ['course' => $course, 'curriculum' => $course->curriculum()]);
    }

    // ===== الوحدات =====

    public function storeTopic(Request $request, Course $course)
    {
        $data = $request->validate(['title' => 'required|string|max:190', 'summary' => 'nullable|string|max:500'], [], ['title' => 'عنوان الوحدة']);
        $course->topics()->create($data + ['sort' => (int) $course->topics()->max('sort') + 1]);

        return back()->with('status', 'تمت إضافة الوحدة.');
    }

    public function updateTopic(Request $request, CourseTopic $topic)
    {
        $topic->update($request->validate([
            'title' => 'required|string|max:190',
            'summary' => 'nullable|string|max:500',
            'sort' => 'nullable|integer',
        ], [], ['title' => 'عنوان الوحدة']));

        return back()->with('status', 'تم حفظ الوحدة.');
    }

    public function destroyTopic(CourseTopic $topic)
    {
        foreach ($topic->lessons as $lesson) {
            Uploader::delete($lesson->attachment);
        }
        $topic->delete();

        return back()->with('status', 'تم حذف الوحدة ومحتواها.');
    }

    // ===== الدروس =====

    public function createLesson(CourseTopic $topic)
    {
        $lesson = new Lesson(['topic_id' => $topic->id, 'course_id' => $topic->course_id, 'sort' => $this->nextSort($topic)]);

        return view('admin.courses.lesson', ['lesson' => $lesson, 'topic' => $topic, 'course' => $topic->course]);
    }

    public function storeLesson(Request $request, CourseTopic $topic)
    {
        $data = $this->lessonData($request);
        if ($request->hasFile('attachment')) {
            $data['attachment'] = Uploader::storePrivate($request->file('attachment'), 'attachments');
            $data['attachment_name'] = $request->file('attachment')->getClientOriginalName();
        }
        $topic->lessons()->create($data + ['course_id' => $topic->course_id]);

        return redirect()->route('admin.courses.curriculum', $topic->course_id)->with('status', 'تمت إضافة الدرس.');
    }

    public function editLesson(Lesson $lesson)
    {
        return view('admin.courses.lesson', ['lesson' => $lesson, 'topic' => $lesson->topic, 'course' => $lesson->course]);
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $data = $this->lessonData($request, $lesson->course_id);
        if ($request->hasFile('attachment')) {
            Uploader::delete($lesson->attachment);
            $data['attachment'] = Uploader::storePrivate($request->file('attachment'), 'attachments');
            $data['attachment_name'] = $request->file('attachment')->getClientOriginalName();
        } elseif ($request->boolean('remove_attachment')) {
            Uploader::delete($lesson->attachment);
            $data['attachment'] = $data['attachment_name'] = null;
        }
        $lesson->update($data);

        return redirect()->route('admin.courses.curriculum', $lesson->course_id)->with('status', 'تم حفظ الدرس.');
    }

    public function destroyLesson(Lesson $lesson)
    {
        Uploader::delete($lesson->attachment);
        $lesson->delete();

        return back()->with('status', 'تم حذف الدرس.');
    }

    // ===== المحاضرات المباشرة (Zoom) =====

    public function createLive(CourseTopic $topic)
    {
        return view('admin.courses.live', [
            'live' => new LiveSession(['duration' => 60, 'sort' => $this->nextSort($topic)]),
            'topic' => $topic, 'course' => $topic->course, 'attendees' => collect(),
        ]);
    }

    public function storeLive(Request $request, CourseTopic $topic)
    {
        $live = $topic->liveSessions()->create($this->liveData($request) + ['course_id' => $topic->course_id]);
        $message = 'تمت إضافة المحاضرة المباشرة.';
        if ($request->boolean('create_zoom') && Zoom::configured() && ! $live->join_url) {
            $message = $this->trySync($live, $message);
        }

        return redirect()->route('admin.live.edit', $live)->with('status', $message);
    }

    public function editLive(LiveSession $live)
    {
        return view('admin.courses.live', [
            'live' => $live, 'topic' => $live->topic, 'course' => $live->course,
            'attendees' => $live->attendances()->with('user')->orderBy('joined_at')->get(),
        ]);
    }

    public function updateLive(Request $request, LiveSession $live)
    {
        $data = $this->liveData($request);
        if ($live->zoom_meeting_id) {
            unset($data['join_url'], $data['password']); // روابط Zoom التلقائية لا تُعدّل يدويًا
        }
        $live->update($data);
        $message = 'تم حفظ المحاضرة.';
        if ($live->zoom_meeting_id && Zoom::configured()) {
            $message = $this->trySync($live, $message.' وتم تحديث موعدها في Zoom.');
        }

        return back()->with('status', $message);
    }

    public function syncLive(LiveSession $live)
    {
        return back()->with('status', $this->trySync($live, 'تم إنشاء اجتماع Zoom ✅'));
    }

    /** المضيف: رابط بدء حديث من Zoom */
    public function hostLive(LiveSession $live)
    {
        $url = Zoom::freshStartUrl($live);
        abort_unless($url, 404);

        return redirect()->away($url);
    }

    public function destroyLive(LiveSession $live)
    {
        $courseId = $live->course_id;
        Zoom::delete($live);
        $live->delete();

        return redirect()->route('admin.courses.curriculum', $courseId)->with('status', 'تم حذف المحاضرة.');
    }

    protected function liveData(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'description' => 'nullable|string|max:3000',
            'starts_at' => 'required|date',
            'duration' => 'required|integer|min:15|max:600',
            'join_url' => 'nullable|url|max:1000',
            'password' => 'nullable|string|max:50',
            'recording_url' => 'nullable|url|max:1000',
            'sort' => 'nullable|integer',
        ], [], ['title' => 'عنوان المحاضرة', 'starts_at' => 'الموعد', 'duration' => 'المدة', 'join_url' => 'رابط الانضمام', 'recording_url' => 'رابط التسجيل']);
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }

    protected function trySync(LiveSession $live, string $success): string
    {
        try {
            Zoom::sync($live->load('course'));

            return $success;
        } catch (\Throwable $e) {
            report($e);

            return '⚠️ حُفظت المحاضرة لكن تعذّر الاتصال بـ Zoom: '.$e->getMessage();
        }
    }

    // ===== الاختبارات =====

    public function storeQuiz(Request $request, CourseTopic $topic)
    {
        $data = $request->validate(['title' => 'required|string|max:190'], [], ['title' => 'عنوان الاختبار']);
        $quiz = $topic->quizzes()->create($data + ['course_id' => $topic->course_id, 'sort' => $this->nextSort($topic)]);

        return redirect()->route('admin.quizzes.edit', $quiz)->with('status', 'تم إنشاء الاختبار ✅ أضف الأسئلة الآن.');
    }

    public function editQuiz(Quiz $quiz)
    {
        return view('admin.courses.quiz', [
            'quiz' => $quiz->load('questions'),
            'course' => $quiz->course,
            'topics' => $quiz->course->topics,
            'question' => new QuizQuestion(['type' => 'single', 'points' => 1]),
        ]);
    }

    public function updateQuiz(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'topic_id' => ['required', Rule::exists('course_topics', 'id')->where('course_id', $quiz->course_id)],
            'description' => 'nullable|string|max:3000',
            'pass_mark' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1|max:600',
            'max_attempts' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|integer',
        ], [], ['pass_mark' => 'درجة النجاح', 'time_limit' => 'الوقت', 'max_attempts' => 'عدد المحاولات', 'topic_id' => 'الوحدة']);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['show_answers'] = $request->boolean('show_answers');
        $quiz->update($data);

        return back()->with('status', 'تم حفظ إعدادات الاختبار.');
    }

    public function destroyQuiz(Quiz $quiz)
    {
        $courseId = $quiz->course_id;
        $quiz->delete();

        return redirect()->route('admin.courses.curriculum', $courseId)->with('status', 'تم حذف الاختبار.');
    }

    // ===== الأسئلة =====

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $quiz->questions()->create($this->questionData($request) + ['sort' => (int) $quiz->questions()->max('sort') + 1]);

        return redirect()->to(route('admin.quizzes.edit', $quiz).'#questions')->with('status', 'تمت إضافة السؤال.');
    }

    public function editQuestion(QuizQuestion $question)
    {
        return view('admin.courses.question', ['question' => $question, 'quiz' => $question->quiz, 'course' => $question->quiz->course]);
    }

    public function updateQuestion(Request $request, QuizQuestion $question)
    {
        $question->update($this->questionData($request));

        return redirect()->to(route('admin.quizzes.edit', $question->quiz_id).'#questions')->with('status', 'تم حفظ السؤال.');
    }

    public function destroyQuestion(QuizQuestion $question)
    {
        $quizId = $question->quiz_id;
        $question->delete();

        return redirect()->to(route('admin.quizzes.edit', $quizId).'#questions')->with('status', 'تم حذف السؤال.');
    }

    // ===== مساعدات =====

    protected function nextSort(CourseTopic $topic): int
    {
        return max((int) $topic->lessons()->max('sort'), (int) $topic->quizzes()->max('sort')) + 1;
    }

    protected function lessonData(Request $request, ?int $courseId = null): array
    {
        $rules = [
            'title' => 'required|string|max:190',
            'video_url' => 'nullable|url|max:255',
            'content' => 'nullable|string',
            'duration' => 'nullable|string|max:20',
            'sort' => 'nullable|integer',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,jpg,jpeg,png|max:20480',
        ];
        if ($courseId) {
            $rules['topic_id'] = ['required', Rule::exists('course_topics', 'id')->where('course_id', $courseId)];
        }
        $data = $request->validate($rules, [], ['video_url' => 'رابط الفيديو', 'content' => 'محتوى الدرس', 'attachment' => 'المرفق', 'topic_id' => 'الوحدة']);
        unset($data['attachment']);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_preview'] = $request->boolean('is_preview');

        return $data;
    }

    protected function questionData(Request $request): array
    {
        $data = $request->validate([
            'question' => 'required|string|max:2000',
            'type' => ['required', Rule::in(array_keys(QuizQuestion::TYPES))],
            'options' => 'array',
            'options.*' => 'nullable|string|max:500',
            'correct' => 'array',
            'explanation' => 'nullable|string|max:2000',
            'points' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|integer',
        ], [], ['question' => 'نص السؤال']);

        $correct = array_map('intval', (array) $request->input('correct', []));
        if ($data['type'] === 'true_false') {
            $texts = ['صح', 'خطأ'];
            $correct = [(int) ($request->input('tf_answer', 0))];
        } else {
            $texts = (array) ($data['options'] ?? []);
        }

        $options = [];
        foreach ($texts as $i => $text) {
            if (trim((string) $text) !== '') {
                $options[] = ['text' => trim($text), 'correct' => in_array((int) $i, $correct, true)];
            }
        }

        $correctCount = count(array_filter($options, fn ($o) => $o['correct']));
        if (count($options) < 2) {
            abort(back()->withInput()->withErrors(['options' => 'أضف خيارين على الأقل.']));
        }
        if ($correctCount === 0) {
            abort(back()->withInput()->withErrors(['correct' => 'حدّد الإجابة الصحيحة.']));
        }
        if ($data['type'] === 'single' && $correctCount > 1) {
            abort(back()->withInput()->withErrors(['correct' => 'هذا السؤال إجابته واحدة — حدّد خيارًا صحيحًا واحدًا أو غيّر النوع إلى "أكثر من إجابة".']));
        }

        return [
            'question' => $data['question'],
            'type' => $data['type'],
            'options' => $options,
            'explanation' => $data['explanation'] ?? null,
            'points' => (int) ($data['points'] ?? 1),
            'sort' => (int) ($data['sort'] ?? 0),
        ];
    }
}
