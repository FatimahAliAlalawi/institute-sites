<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * استيراد الدورات والطلاب من موقع ووردبريس + Tutor LMS (بالاتصال المباشر بقاعدة بياناته).
 * آمن لإعادة التشغيل: كل عنصر يُسجَّل برقمه القديم في جدول import_maps فلا يتكرر.
 */
class TutorImporter
{
    protected const FILE_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'mp4', 'webm'];

    protected Connection $wp;

    protected string $prefix;

    protected ?string $uploadsPath;

    protected ?string $siteUrl;

    protected array $counts = [];

    protected array $warnings = [];

    protected array $metaCache = [];

    /** وضع المزامنة: الدورات الموجودة تُكمَّل بالجديد بدل تخطيها */
    protected bool $syncing = false;

    public function __construct(array $db, array $options = [])
    {
        $this->prefix = preg_match('/^[A-Za-z0-9_]{0,30}$/', $options['prefix'] ?? 'wp_') ? ($options['prefix'] ?? 'wp_') : 'wp_';
        $this->uploadsPath = ($p = rtrim((string) ($options['uploads_path'] ?? ''), '/\\')) !== '' ? $p : null;
        $this->siteUrl = ($u = rtrim((string) ($options['site_url'] ?? ''), '/')) !== '' ? $u : null;

        $config = ($db['driver'] ?? 'mysql') === 'sqlite'
            ? ['driver' => 'sqlite', 'database' => $db['database'], 'prefix' => '', 'foreign_key_constraints' => false]
            : [
                'driver' => 'mysql',
                'host' => $db['host'] ?? 'localhost',
                'port' => $db['port'] ?? 3306,
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => (string) ($db['password'] ?? ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'options' => [\PDO::ATTR_TIMEOUT => 10],
            ];
        config(['database.connections.tutor_import' => $config]);
        DB::purge('tutor_import');
        $this->wp = DB::connection('tutor_import');
    }

    // ================= المعاينة =================

    /** يتحقق من الاتصال ويعدّ ما سيُستورد، بدون أي تعديل */
    public function preview(): array
    {
        $this->assertTutorDatabase();
        $posts = fn (string $type) => $this->posts()->where('post_type', $type)->count();

        return [
            'الدورات' => $posts('courses'),
            'الوحدات' => $posts('topics'),
            'الدروس' => $posts('lesson'),
            'الواجبات (تُستورد كدروس)' => $posts('tutor_assignments'),
            'الاختبارات' => $posts('tutor_quiz'),
            'الأسئلة' => $this->hasTable('tutor_quiz_questions') ? $this->wp->table($this->t('tutor_quiz_questions'))->count() : 0,
            'الاشتراكات' => $posts('tutor_enrolled'),
            'الطلاب' => $this->posts()->where('post_type', 'tutor_enrolled')->distinct()->count('post_author'),
            'التقييمات' => $this->wp->table($this->t('comments'))->where('comment_type', 'tutor_course_rating')->count(),
            'الدورات المكتملة (تُصدر لها شهادات)' => $this->wp->table($this->t('comments'))->where('comment_type', 'course_completed')->count(),
        ];
    }

    // ================= الاستيراد =================

    public function run(bool $students = true, bool $progress = true, bool $reviews = true): array
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        $this->assertTutorDatabase();

        $this->importCategories();
        foreach ($this->posts()->where('post_type', 'courses')->orderBy('menu_order')->orderBy('ID')->get() as $row) {
            try {
                DB::transaction(fn () => $this->importCourse($row));
            } catch (\Throwable $e) {
                $this->warn('تعذّر استيراد الدورة «'.$this->decode($row->post_title).'»: '.$e->getMessage());
            }
        }

        if ($students) {
            $this->importEnrollments();
        }
        if ($students && $progress) {
            $this->importProgress();
        }
        if ($reviews) {
            $this->importReviews();
        }

        return ['counts' => $this->counts, 'warnings' => $this->warnings];
    }

    /**
     * المزامنة المستمرة مع الموقع القديم: الدورات والدروس والفيديوهات الجديدة،
     * كل من سجّل حسابًا، الاشتراكات الجديدة وتفعيل المدفوعة، والتقدّم.
     */
    public function sync(): array
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        $this->assertTutorDatabase();
        $this->syncing = true;

        $this->importCategories();
        foreach ($this->posts()->where('post_type', 'courses')->orderBy('menu_order')->orderBy('ID')->get() as $row) {
            try {
                DB::transaction(fn () => $this->importCourse($row));
            } catch (\Throwable $e) {
                $this->warn('تعذّر مزامنة الدورة «'.$this->decode($row->post_title).'»: '.$e->getMessage());
            }
        }
        $this->importUsers();
        $this->importEnrollments();
        $this->importProgress();
        $this->importReviews();

        return ['counts' => $this->counts, 'warnings' => $this->warnings];
    }

    /** مزامنة طالب واحد فورًا (عند دخوله أو فتح لوحته): حسابه واشتراكاته */
    public function syncEmail(string $email): ?int
    {
        $this->syncing = true;
        $row = $this->wp->table($this->t('users'))->where('user_email', mb_strtolower(trim($email)))->first();
        if (! $row || $this->isAdmin((int) $row->ID)) {
            return null;
        }
        $userId = $this->importUser((int) $row->ID);
        if ($userId) {
            $this->refreshLegacyPassword($userId, $row->user_pass);
            $this->importEnrollments((int) $row->ID);
        }

        return $userId;
    }

    // ----- التصنيفات والمدربون -----

    protected function importCategories(): void
    {
        $rows = $this->wp->table($this->t('term_taxonomy').' as tt')
            ->join($this->t('terms').' as t', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'course-category')
            ->select('tt.term_taxonomy_id', 't.name')->get();

        foreach ($rows as $row) {
            if ($this->mapped('category', $row->term_taxonomy_id, CourseCategory::class)) {
                continue;
            }
            $category = CourseCategory::firstOrCreate(['name' => $this->decode($row->name)]);
            $this->remember('category', $row->term_taxonomy_id, $category->id);
            $this->bump('التصنيفات');
        }
    }

    protected function categoryFor(int $courseId): ?int
    {
        $termId = $this->wp->table($this->t('term_relationships').' as tr')
            ->join($this->t('term_taxonomy').' as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $courseId)->where('tt.taxonomy', 'course-category')
            ->value('tt.term_taxonomy_id');

        return $termId ? $this->mapped('category', $termId, CourseCategory::class) : null;
    }

    protected function importInstructor(int $wpUserId): ?int
    {
        if (! $wpUserId) {
            return null;
        }
        if ($id = $this->mapped('instructor', $wpUserId, Instructor::class)) {
            return $id;
        }
        $user = $this->wp->table($this->t('users'))->where('ID', $wpUserId)->first();
        if (! $user) {
            return null;
        }
        $meta = $this->userMeta($wpUserId);
        $photo = ! empty($meta['_tutor_profile_photo']) ? $this->copyAttachment((int) $meta['_tutor_profile_photo'], 'instructors') : null;

        $instructor = Instructor::create([
            'name' => $this->displayName($user, $meta),
            'title' => Str::limit($this->text($meta['_tutor_profile_job_title'] ?? ''), 180, '') ?: null,
            'bio' => $this->text($meta['_tutor_profile_bio'] ?? ($meta['description'] ?? '')) ?: null,
            'photo' => $photo['path'] ?? null,
        ]);
        $this->remember('instructor', $wpUserId, $instructor->id);
        $this->bump('المدربون');

        return $instructor->id;
    }

    // ----- الدورات والمنهج -----

    protected function importCourse(object $row): void
    {
        $title = $this->decode($row->post_title) ?: 'دورة بدون عنوان';
        if ($id = $this->mapped('course', $row->ID, Course::class)) {
            if ($this->syncing) {
                $this->importCurriculum(Course::find($id), $row->ID, $title);
            } else {
                $this->bump('دورات موجودة مسبقًا (تم تخطيها)');
            }

            return;
        }

        $meta = $this->postMeta($row->ID);
        [$price, $sale, $priceOk] = $this->coursePrice($meta, $title);
        $video = $this->video($meta['_video'] ?? null);

        $course = Course::create([
            'title' => $title,
            'slug' => urldecode((string) $row->post_name) ?: null,
            'summary' => Str::limit($this->text($row->post_excerpt), 480, '') ?: null,
            'description' => $this->text($row->post_content) ?: null,
            'what_you_learn' => $this->text($meta['_tutor_course_benefits'] ?? '') ?: null,
            'requirements' => $this->text($meta['_tutor_course_requirements'] ?? '') ?: null,
            'target_audience' => Str::limit(str_replace("\n", '، ', $this->text($meta['_tutor_course_target_audience'] ?? '')), 180, '') ?: null,
            'level' => ['beginner' => 'beginner', 'intermediate' => 'intermediate', 'expert' => 'advanced'][$meta['_tutor_course_level'] ?? ''] ?? 'all',
            'duration_text' => $this->durationText($meta['_course_duration'] ?? null),
            'intro_video' => $video['url'],
            'price' => $price,
            'sale_price' => $sale,
            'category_id' => $this->categoryFor($row->ID),
            'instructor_id' => $this->importInstructor((int) $row->post_author),
            'certificate_enabled' => true,
            'is_published' => $row->post_status === 'publish' && $priceOk,
            'sort' => (int) $row->menu_order,
        ]);
        $course->forceFill(['created_at' => $this->date($row->post_date)])->save();

        if (! empty($meta['_thumbnail_id'])) {
            $thumb = $this->copyAttachment((int) $meta['_thumbnail_id'], 'courses');
            $course->update(['thumbnail' => $thumb['path'] ?? $thumb['url'] ?? null]);
        }
        $this->remember('course', $row->ID, $course->id);
        $this->bump('الدورات');

        $this->importCurriculum($course, $row->ID, $title);
    }

    /** الوحدات والدروس والاختبارات — ما سبق استيراده يُتخطى، فيصلح لإضافة الجديد فقط */
    protected function importCurriculum(Course $course, int $wpCourseId, string $title): void
    {
        $topics = $this->posts()->where('post_type', 'topics')->where('post_parent', $wpCourseId)->orderBy('menu_order')->orderBy('ID')->get();
        foreach ($topics as $i => $t) {
            if ($topicId = $this->mapped('topic', $t->ID, CourseTopic::class)) {
                $topic = CourseTopic::find($topicId);
            } else {
                $topic = $course->topics()->create([
                    'title' => $this->decode($t->post_title) ?: 'وحدة '.($i + 1),
                    'summary' => Str::limit($this->text($t->post_content), 480, '') ?: null,
                    'sort' => (int) $t->menu_order ?: $i + 1,
                ]);
                $this->remember('topic', $t->ID, $topic->id);
                $this->bump('الوحدات');
            }

            $items = $this->posts()->where('post_parent', $t->ID)->orderBy('menu_order')->orderBy('ID')->get();
            foreach ($items as $item) {
                if (in_array($item->post_type, ['lesson', 'tutor_assignments'], true) && ($lessonId = $this->mapped('lesson', $item->ID, Lesson::class))) {
                    $this->fillMissingVideo($lessonId, $item->ID);

                    continue;
                }
                if ($item->post_type === 'tutor_quiz' && $this->mapped('quiz', $item->ID, Quiz::class)) {
                    continue;
                }
                match ($item->post_type) {
                    'lesson' => $this->importLesson($item, $topic, false),
                    'tutor_assignments' => $this->importLesson($item, $topic, true),
                    'tutor_quiz' => $this->importQuiz($item, $topic),
                    default => $this->warn('عنصر من نوع «'.$item->post_type.'» في دورة «'.$title.'» غير مدعوم وتم تخطيه: '.$this->decode($item->post_title)),
                };
            }
        }
    }

    /** درس استُورد بدون فيديو ثم أُضيف له فيديو في الموقع القديم */
    protected function fillMissingVideo(int $lessonId, int $wpLessonId): void
    {
        $lesson = Lesson::find($lessonId);
        if (! $lesson || filled($lesson->video_url)) {
            return;
        }
        $video = $this->video($this->postMeta($wpLessonId)['_video'] ?? null);
        if ($video['url']) {
            $lesson->update(['video_url' => $video['url'], 'duration' => $lesson->duration ?: $video['runtime']]);
            $this->bump('فيديوهات أُضيفت لدروس موجودة');
        }
    }

    protected function importLesson(object $row, CourseTopic $topic, bool $assignment): void
    {
        $meta = $this->postMeta($row->ID);
        $video = $this->video($meta['_video'] ?? null);
        $content = $this->text($row->post_content);

        $ids = array_merge(
            (array) ($this->unserialize($meta['_tutor_attachments'] ?? null) ?: []),
            (array) ($this->unserialize($meta['_tutor_assignment_attachments'] ?? null) ?: [])
        );
        $attachment = null;
        $extra = [];
        foreach (array_values(array_filter(array_map('intval', $ids))) as $n => $id) {
            $file = $this->copyAttachment($id, 'attachments', $n === 0, $n === 0);
            if ($n === 0 && ! empty($file['path'])) {
                $attachment = $file;
            } elseif ($file) {
                $extra[] = '- '.$file['name'].': '.$file['url'];
            }
        }
        if ($extra) {
            $content .= "\n\nمرفقات إضافية:\n".implode("\n", $extra);
        }
        if ($assignment) {
            $content = "📝 واجب: نفّذ المطلوب وأرسله للمدرب.\n\n".$content;
        }

        $lesson = $topic->lessons()->create([
            'course_id' => $topic->course_id,
            'title' => ($assignment ? 'واجب: ' : '').($this->decode($row->post_title) ?: 'درس'),
            'video_url' => $video['url'],
            'duration' => $video['runtime'],
            'content' => trim($content) ?: null,
            'attachment' => $attachment['path'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'is_preview' => ($meta['_is_preview'] ?? '') === '1',
            'sort' => (int) $row->menu_order,
        ]);
        $this->remember('lesson', $row->ID, $lesson->id);
        $this->bump($assignment ? 'الواجبات (كدروس)' : 'الدروس');
    }

    protected function importQuiz(object $row, CourseTopic $topic): void
    {
        $option = (array) ($this->unserialize($this->postMeta($row->ID)['tutor_quiz_option'] ?? null) ?: []);
        $time = (array) ($option['time_limit'] ?? []);
        $value = (int) ($time['time_value'] ?? 0);
        $minutes = match ($time['time_type'] ?? 'minutes') {
            'seconds' => (int) ceil($value / 60),
            'hours' => $value * 60,
            'days' => $value * 1440,
            'weeks' => $value * 10080,
            default => $value,
        };

        $quiz = $topic->quizzes()->create([
            'course_id' => $topic->course_id,
            'title' => $this->decode($row->post_title) ?: 'اختبار',
            'description' => $this->text($row->post_content) ?: null,
            'pass_mark' => max(0, min(100, (int) ($option['passing_grade'] ?? 80))),
            'time_limit' => $minutes > 0 ? min($minutes, 600) : null,
            'max_attempts' => ($a = (int) ($option['attempts_allowed'] ?? 0)) > 0 ? min($a, 100) : null,
            'show_answers' => true,
            'sort' => (int) $row->menu_order,
        ]);
        $this->remember('quiz', $row->ID, $quiz->id);
        $this->bump('الاختبارات');

        if (! $this->hasTable('tutor_quiz_questions')) {
            return;
        }
        $questions = $this->wp->table($this->t('tutor_quiz_questions'))->where('quiz_id', $row->ID)
            ->orderBy('question_order')->orderBy('question_id')->get();
        foreach ($questions as $q) {
            $this->importQuestion($q, $quiz);
        }
    }

    protected function importQuestion(object $q, Quiz $quiz): void
    {
        $type = ['single_choice' => 'single', 'multiple_choice' => 'multiple', 'true_false' => 'true_false'][$q->question_type] ?? null;
        $title = $this->text($q->question_title);
        if (! $type) {
            $this->bump('أسئلة غير مدعومة (تم تخطيها)');
            $this->warn('سؤال من نوع «'.$q->question_type.'» في اختبار «'.$quiz->title.'» تم تخطيه — أضفه يدويًا إن لزم: '.Str::limit($title, 80));

            return;
        }

        $answers = $this->wp->table($this->t('tutor_quiz_question_answers'))->where('belongs_question_id', $q->question_id)
            ->orderBy('answer_order')->orderBy('answer_id')->get();

        if ($type === 'true_false') {
            $correct = $answers->first(fn ($a) => (int) $a->is_correct === 1);
            $isTrue = $correct && in_array(mb_strtolower(trim($this->decode($correct->answer_title))), ['true', 'صح', 'صحيح', 'نعم'], true);
            $options = [['text' => 'صح', 'correct' => $isTrue], ['text' => 'خطأ', 'correct' => ! $isTrue]];
        } else {
            $options = $answers->map(fn ($a) => ['text' => $this->decode($a->answer_title), 'correct' => (int) $a->is_correct === 1])
                ->filter(fn ($o) => $o['text'] !== '')->values()->all();
        }

        $correctCount = count(array_filter($options, fn ($o) => $o['correct']));
        if (count($options) < 2 || $correctCount === 0) {
            $this->bump('أسئلة غير مكتملة (تم تخطيها)');
            $this->warn('سؤال بدون خيارات صالحة أو بدون إجابة صحيحة في «'.$quiz->title.'»: '.Str::limit($title, 80));

            return;
        }
        if ($type === 'single' && $correctCount > 1) {
            $type = 'multiple';
        }

        $description = $this->text($q->question_description ?? '');
        $question = $quiz->questions()->create([
            'question' => trim($title.($description ? "\n".$description : '')) ?: 'سؤال',
            'type' => $type,
            'options' => $options,
            'explanation' => $this->text($q->answer_explanation ?? '') ?: null,
            'points' => max(1, (int) round((float) ($q->question_mark ?? 1))),
            'sort' => (int) $q->question_order,
        ]);
        $this->remember('question', $q->question_id, $question->id);
        $this->bump('الأسئلة');
    }

    // ----- الطلاب والاشتراكات -----

    protected function importUser(int $wpUserId): ?int
    {
        if (! $wpUserId) {
            return null;
        }
        if ($id = $this->mapped('user', $wpUserId, User::class)) {
            return $id;
        }
        $row = $this->wp->table($this->t('users'))->where('ID', $wpUserId)->first();
        $email = mb_strtolower(trim((string) ($row->user_email ?? '')));
        if (! $row || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->warn('مستخدم رقم '.$wpUserId.' بدون بريد إلكتروني صالح — تم تخطيه.');

            return null;
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $this->bump('طلاب موجودون مسبقًا (تم ربطهم)');
        } else {
            $meta = $this->userMeta($wpUserId);
            $phone = preg_replace('/[^0-9+]/', '', (string) ($meta['phone_number'] ?? $meta['billing_phone'] ?? $meta['phone'] ?? ''));
            $user = new User;
            $user->forceFill([
                'name' => Str::limit($this->displayName($row, $meta), 150, ''),
                'email' => $email,
                'phone' => $phone !== '' ? Str::limit($phone, 20, '') : null,
                'password' => Str::random(40),
                'legacy_password' => $row->user_pass ?: null,
                'is_active' => true,
            ])->save();
            $user->forceFill(['created_at' => $this->date($row->user_registered)])->save();
            $this->bump('الطلاب');
        }
        $this->remember('user', $wpUserId, $user->id);

        return $user->id;
    }

    /** كل من أنشأ حسابًا في الموقع القديم (عدا المدراء) — حتى لو لم يشترك بعد */
    protected function importUsers(): void
    {
        $known = DB::table('import_maps')->where('type', 'user')->pluck('local_id', 'legacy_id')->all();
        $rows = $this->wp->table($this->t('users'))->orderBy('ID')->get(['ID', 'user_pass']);
        foreach ($rows as $row) {
            if (isset($known[(string) $row->ID])) {
                $this->refreshLegacyPassword((int) $known[(string) $row->ID], $row->user_pass);

                continue;
            }
            if (! $this->isAdmin((int) $row->ID)) {
                $this->importUser((int) $row->ID);
            }
        }
    }

    protected function isAdmin(int $wpUserId): bool
    {
        $caps = (string) $this->wp->table($this->t('usermeta'))->where('user_id', $wpUserId)
            ->where('meta_key', $this->prefix.'capabilities')->value('meta_value');

        return str_contains($caps, '"administrator"');
    }

    /** طالب لم يدخل الموقع الجديد بعد وغيّر كلمة مروره في القديم ← تُحدَّث */
    protected function refreshLegacyPassword(int $userId, ?string $hash): void
    {
        if ($hash) {
            User::whereKey($userId)->whereNotNull('legacy_password')->where('legacy_password', '!=', $hash)
                ->update(['legacy_password' => $hash]);
        }
    }

    protected function importEnrollments(?int $wpUserId = null): void
    {
        $rows = $this->posts()->where('post_type', 'tutor_enrolled')
            ->when($wpUserId, fn ($q) => $q->where('post_author', $wpUserId))->orderBy('ID')->get();
        foreach ($rows as $row) {
            $status = match ($row->post_status) {
                'completed', 'publish' => 'active',
                'cancel', 'canceled', 'cancelled', 'trash' => 'cancelled',
                default => 'pending',
            };
            if ($id = $this->mapped('enrollment', $row->ID, Enrollment::class)) {
                // اشتراك كان معلّقًا ثم دُفع/فُعّل في الموقع القديم ← يُفعَّل هنا أيضًا
                $existing = Enrollment::find($id);
                if ($status === 'active' && $existing->status === 'pending') {
                    $existing->forceFill(['status' => 'active', 'approved_at' => now()])->save();
                    $this->bump('اشتراكات فُعّلت');
                }

                continue;
            }
            $courseId = $this->mapped('course', $row->post_parent, Course::class);
            $userId = $courseId ? $this->importUser((int) $row->post_author) : null;
            if (! $courseId || ! $userId) {
                continue;
            }

            $course = Course::find($courseId);
            $enrollment = Enrollment::firstOrNew(['user_id' => $userId, 'course_id' => $courseId]);
            if (! $enrollment->exists) {
                $enrollment->forceFill([
                    'status' => $status,
                    'amount' => $course->is_free ? 0 : ($course->effective_price ?: 0),
                    'payment_method' => $course->is_free ? 'free' : 'imported',
                    'approved_at' => $status === 'active' ? $this->date($row->post_date) : null,
                    'created_at' => $this->date($row->post_date),
                ])->save();
                $this->bump('الاشتراكات');
            }
            $this->remember('enrollment', $row->ID, $enrollment->id);
        }
    }

    protected function importProgress(): void
    {
        // الدروس المكتملة
        $prefix = '_tutor_completed_lesson_id_';
        $rows = $this->wp->table($this->t('usermeta'))->where('meta_key', 'like', $prefix.'%')->get(['user_id', 'meta_key'])
            ->filter(fn ($row) => str_starts_with($row->meta_key, $prefix));
        $lessonCourse = [];
        foreach ($rows as $row) {
            $userId = $this->mapped('user', $row->user_id, User::class);
            $lessonId = $this->mapped('lesson', substr($row->meta_key, strlen($prefix)), Lesson::class);
            if (! $userId || ! $lessonId) {
                continue;
            }
            $lessonCourse[$lessonId] ??= Lesson::whereKey($lessonId)->value('course_id');
            $done = LessonCompletion::firstOrCreate(['user_id' => $userId, 'lesson_id' => $lessonId], ['course_id' => $lessonCourse[$lessonId]]);
            if ($done->wasRecentlyCreated) {
                $this->bump('الدروس المكتملة');
            }
        }

        // محاولات الاختبارات المنتهية
        if ($this->hasTable('tutor_quiz_attempts')) {
            $attempts = $this->wp->table($this->t('tutor_quiz_attempts'))->where('attempt_status', 'attempt_ended')->orderBy('attempt_id')->get();
            foreach ($attempts as $a) {
                if ($this->mapped('attempt', $a->attempt_id, QuizAttempt::class)) {
                    continue;
                }
                $userId = $this->mapped('user', $a->user_id, User::class);
                $quiz = ($quizId = $this->mapped('quiz', $a->quiz_id, Quiz::class)) ? Quiz::find($quizId) : null;
                if (! $userId || ! $quiz) {
                    continue;
                }
                $total = (float) $a->total_marks;
                $score = $total > 0 ? (int) round((float) $a->earned_marks * 100 / $total) : 0;
                $attempt = new QuizAttempt;
                $attempt->forceFill([
                    'user_id' => $userId, 'quiz_id' => $quiz->id, 'course_id' => $quiz->course_id,
                    'score' => max(0, min(100, $score)),
                    'earned_points' => (int) round((float) $a->earned_marks),
                    'total_points' => (int) round($total),
                    'passed' => $score >= $quiz->pass_mark,
                    'answers' => [],
                    'created_at' => $this->date($a->attempt_ended_at ?? $a->attempt_started_at ?? null),
                ])->save();
                $this->remember('attempt', $a->attempt_id, $attempt->id);
                $this->bump('نتائج الاختبارات');
            }
        }

        // الدورات المكتملة ← إكمال الاشتراك + إصدار الشهادة
        $done = $this->wp->table($this->t('comments'))->where('comment_type', 'course_completed')->get(['user_id', 'comment_post_ID', 'comment_date']);
        foreach ($done as $row) {
            $userId = $this->mapped('user', $row->user_id, User::class) ?? $this->importUser((int) $row->user_id);
            $course = ($courseId = $this->mapped('course', $row->comment_post_ID, Course::class)) ? Course::find($courseId) : null;
            if (! $userId || ! $course) {
                continue;
            }
            $date = $this->date($row->comment_date);
            $enrollment = Enrollment::firstOrNew(['user_id' => $userId, 'course_id' => $course->id]);
            if (! $enrollment->exists) {
                $enrollment->forceFill(['status' => 'active', 'payment_method' => $course->is_free ? 'free' : 'imported', 'approved_at' => $date]);
            }
            $enrollment->completed_at ??= $date;
            $enrollment->save();

            // الدورة مكتملة في النظام القديم ← كل دروسها مكتملة (حتى يطابق شريط التقدم الشهادة)
            foreach ($course->lessons()->pluck('id') as $lessonId) {
                LessonCompletion::firstOrCreate(['user_id' => $userId, 'lesson_id' => $lessonId], ['course_id' => $course->id]);
            }

            if ($course->certificate_enabled) {
                $certificate = Certificate::firstOrCreate(
                    ['user_id' => $userId, 'course_id' => $course->id],
                    ['code' => strtoupper(Str::random(4).'-'.Str::random(4)), 'issued_at' => $date]
                );
                if ($certificate->wasRecentlyCreated) {
                    $this->bump('الشهادات');
                }
            }
        }
    }

    protected function importReviews(): void
    {
        $rows = $this->wp->table($this->t('comments'))->where('comment_type', 'tutor_course_rating')->orderBy('comment_ID')->get();
        foreach ($rows as $row) {
            if ($this->mapped('review', $row->comment_ID, Review::class)) {
                continue;
            }
            $courseId = $this->mapped('course', $row->comment_post_ID, Course::class);
            $userId = $courseId ? ($this->mapped('user', $row->user_id, User::class) ?? $this->importUser((int) $row->user_id)) : null;
            $rating = (int) $this->wp->table($this->t('commentmeta'))->where('comment_id', $row->comment_ID)->where('meta_key', 'tutor_rating')->value('meta_value');
            if (! $courseId || ! $userId || $rating < 1) {
                continue;
            }
            $review = Review::firstOrNew(['user_id' => $userId, 'course_id' => $courseId]);
            $review->forceFill([
                'rating' => min(5, $rating),
                'comment' => $this->text($row->comment_content) ?: null,
                'is_approved' => (string) $row->comment_approved === '1',
                'created_at' => $this->date($row->comment_date),
            ])->save();
            $this->remember('review', $row->comment_ID, $review->id);
            $this->bump('التقييمات');
        }
    }

    // ================= مساعدات =================

    protected function assertTutorDatabase(): void
    {
        $this->wp->getPdo();
        if (! $this->hasTable('posts') || ! $this->hasTable('postmeta') || ! $this->hasTable('users')) {
            throw new \RuntimeException('لم يُعثر على جداول ووردبريس بالبادئة «'.$this->prefix.'» — تأكد من البادئة (table_prefix في wp-config.php).');
        }
    }

    protected function t(string $table): string
    {
        return $this->prefix.$table;
    }

    protected function hasTable(string $table): bool
    {
        static $cache = [];

        return $cache[$this->prefix.$table] ??= $this->wp->getSchemaBuilder()->hasTable($this->t($table));
    }

    protected function posts()
    {
        return $this->wp->table($this->t('posts'))->whereNotIn('post_status', ['trash', 'auto-draft', 'inherit']);
    }

    protected function postMeta(int $postId): array
    {
        return $this->metaCache['p'.$postId] ??= $this->wp->table($this->t('postmeta'))->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')->all();
    }

    protected function userMeta(int $userId): array
    {
        return $this->wp->table($this->t('usermeta'))->where('user_id', $userId)->pluck('meta_value', 'meta_key')->all();
    }

    protected function mapped(string $type, $legacyId, string $model): ?int
    {
        $id = DB::table('import_maps')->where('type', $type)->where('legacy_id', (string) $legacyId)->value('local_id');

        return $id && $model::whereKey($id)->exists() ? (int) $id : null;
    }

    protected function remember(string $type, $legacyId, int $localId): void
    {
        DB::table('import_maps')->updateOrInsert(
            ['type' => $type, 'legacy_id' => (string) $legacyId],
            ['local_id' => $localId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    protected function bump(string $label, int $by = 1): void
    {
        $this->counts[$label] = ($this->counts[$label] ?? 0) + $by;
    }

    protected function warn(string $message): void
    {
        if (count($this->warnings) < 300) {
            $this->warnings[] = $message;
        }
    }

    protected function unserialize($value)
    {
        if (! is_string($value) || ! preg_match('/^(a|s|i|b|d|N):/', trim($value))) {
            return $value;
        }
        $result = @unserialize(trim($value), ['allowed_classes' => false]);

        return $result === false && trim($value) !== 'b:0;' ? $value : $result;
    }

    protected function decode(?string $text): string
    {
        return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected function date($value): \Illuminate\Support\Carbon
    {
        try {
            return $value && ! str_starts_with((string) $value, '0000') ? \Illuminate\Support\Carbon::parse($value) : now();
        } catch (\Throwable) {
            return now();
        }
    }

    protected function displayName(object $user, array $meta): string
    {
        $full = trim(($meta['first_name'] ?? '').' '.($meta['last_name'] ?? ''));

        return $this->decode($full !== '' ? $full : ($user->display_name ?: $user->user_login)) ?: 'طالب';
    }

    /** تحويل HTML ووردبريس إلى نص مرتّب (فقرات وقوائم وروابط) */
    public function text(?string $html): string
    {
        $s = (string) $html;
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/<!--.*?-->/s', '', $s);
        $s = preg_replace('#<(script|style)[^>]*>.*?</\1>#si', '', $s);
        $s = preg_replace('#\[/?(caption|embed|video|audio|gallery|vc_[a-z_]+|et_pb_[a-z_]+)[^\]]*\]#i', '', $s);
        $s = preg_replace_callback('#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#si', function ($m) {
            $label = trim(strip_tags($m[2]));

            return $label === '' || $label === $m[1] ? $m[1] : $label.' ('.$m[1].')';
        }, $s);
        $s = preg_replace('#<li[^>]*>#i', "\n• ", $s);
        $s = preg_replace('#<br\s*/?>#i', "\n", $s);
        $s = preg_replace('#</(p|div|h[1-6]|li|ul|ol|tr|blockquote|table|figure)>#i', "\n", $s);
        $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $s);
        $s = preg_replace("/[ \t]+\n/", "\n", $s);
        $s = preg_replace("/\n{3,}/", "\n\n", $s);

        return trim($s);
    }

    protected function durationText($raw): ?string
    {
        $d = (array) ($this->unserialize($raw) ?: []);
        $h = (int) ($d['hours'] ?? 0);
        $m = (int) ($d['minutes'] ?? 0);
        $parts = array_filter([$h ? $h.' ساعة' : null, $m ? $m.' دقيقة' : null]);

        return $parts ? implode(' و', $parts) : null;
    }

    /** @return array{url:?string,runtime:?string} */
    protected function video($raw): array
    {
        $v = $this->unserialize($raw);
        if (! is_array($v)) {
            return ['url' => null, 'runtime' => null];
        }

        $url = match ($v['source'] ?? '') {
            'youtube' => $v['source_youtube'] ?? null,
            'vimeo' => $v['source_vimeo'] ?? null,
            'external_url' => $v['source_external_url'] ?? null,
            'embedded' => preg_match('/src=["\']([^"\']+)["\']/i', (string) ($v['source_embedded'] ?? ''), $m) ? $m[1] : null,
            'html5' => ! empty($v['source_video_id']) ? ($this->copyAttachment((int) $v['source_video_id'], 'videos', false, false)['path_or_url'] ?? null) : null,
            default => null,
        };
        if ($url && ! preg_match('#^https?://#', $url) && str_starts_with($url, 'uploads/')) {
            $url = asset($url);
        }
        if ($url && (strlen($url) > 255 || ! preg_match('#^https?://#i', $url))) {
            $this->warn('رابط فيديو غير صالح أو طويل جدًا وتم تجاهله: '.Str::limit($url, 80));
            $url = null;
        }

        $r = (array) ($v['runtime'] ?? []);
        $h = (int) ($r['hours'] ?? 0);
        $m = (int) ($r['minutes'] ?? 0);
        $s = (int) ($r['seconds'] ?? 0);
        $runtime = ($h || $m || $s) ? ($h ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s)) : null;

        return ['url' => $url, 'runtime' => $runtime];
    }

    /** @return array{0:?float,1:?float,2:bool} [السعر، سعر الخصم، هل السعر معروف] */
    protected function coursePrice(array $meta, string $title): array
    {
        if (($meta['_tutor_course_price_type'] ?? 'free') !== 'paid') {
            return [0, null, true];
        }
        $price = $meta['tutor_course_price'] ?? null;
        $sale = $meta['tutor_course_sale_price'] ?? null;
        if (! is_numeric($price) && ! empty($meta['_tutor_course_product_id'])) {
            $product = $this->postMeta((int) $meta['_tutor_course_product_id']);
            $price = $product['_regular_price'] ?? ($product['_price'] ?? ($product['edd_price'] ?? null));
            $sale = $product['_sale_price'] ?? null;
        }
        if (! is_numeric($price) || (float) $price <= 0) {
            $this->warn('الدورة «'.$title.'» مدفوعة لكن لم يُعثر على سعرها — استُوردت كمسودة، حدّد السعر ثم انشرها.');

            return [null, null, false];
        }

        return [(float) $price, is_numeric($sale) && (float) $sale > 0 ? (float) $sale : null, true];
    }

    /**
     * نسخ ملف من مكتبة وسائط ووردبريس: من مجلد uploads على السيرفر (الأسرع)، أو تنزيله من رابطه.
     *
     * @return array{path:?string,url:?string,name:string,path_or_url:?string}|null
     */
    protected function copyAttachment(int $attachmentId, string $folder, bool $private = false, bool $allowHttp = true): ?array
    {
        $post = $this->wp->table($this->t('posts'))->where('ID', $attachmentId)->where('post_type', 'attachment')->first();
        if (! $post) {
            return null;
        }
        $relative = ltrim((string) ($this->postMeta($attachmentId)['_wp_attached_file'] ?? ''), '/');
        $url = $relative !== '' && $this->siteUrl ? $this->siteUrl.'/wp-content/uploads/'.$relative : ((string) $post->guid ?: null);
        $name = basename($relative !== '' ? $relative : (string) parse_url((string) $url, PHP_URL_PATH)) ?: 'file';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $result = ['path' => null, 'url' => $url, 'name' => $name, 'path_or_url' => $url];

        if (! in_array($ext, self::FILE_TYPES, true)) {
            $this->warn('نوع ملف غير مسموح وتم تجاهله: '.$name);

            return $result;
        }

        $dir = $private ? storage_path('app/private/'.$folder) : public_path('uploads/'.$folder);
        @mkdir($dir, 0755, true);
        $file = date('Ymd').'-'.Str::random(16).'.'.$ext;
        $saved = false;

        $source = $this->uploadsPath && $relative !== '' && ! str_contains($relative, '..') ? $this->uploadsPath.DIRECTORY_SEPARATOR.$relative : null;
        if ($source && is_file($source)) {
            $saved = @copy($source, $dir.DIRECTORY_SEPARATOR.$file);
        } elseif ($allowHttp && $url && preg_match('#^https?://#i', $url)) {
            try {
                $response = Http::timeout(20)->withOptions(['verify' => false])->get($url);
                if ($response->successful() && strlen($response->body()) <= 50 * 1024 * 1024) {
                    $saved = file_put_contents($dir.DIRECTORY_SEPARATOR.$file, $response->body()) !== false;
                }
            } catch (\Throwable) {
                $saved = false;
            }
        }

        if (! $saved) {
            if ($allowHttp || $folder !== 'videos') {
                $this->warn('تعذّر نسخ الملف «'.$name.'» — '.($private ? 'لم يُرفق' : 'بقي رابطًا للموقع القديم').'. '
                    .($this->uploadsPath ? 'الملف غير موجود في مجلد uploads المحدد.' : 'حدّد مسار مجلد uploads لنسخه.'));
            }

            return $result;
        }
        $this->bump('الملفات المنسوخة');
        $path = ($private ? 'private/' : 'uploads/').$folder.'/'.$file;

        return ['path' => $path, 'url' => $url, 'name' => $name, 'path_or_url' => $private ? $url : $path];
    }
}
