<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasSlug;

    public const LEVELS = [
        'all' => 'جميع المستويات',
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ];

    protected $fillable = [
        'category_id', 'instructor_id', 'title', 'slug', 'summary', 'description', 'what_you_learn', 'requirements',
        'target_audience', 'level', 'duration_text', 'thumbnail', 'intro_video', 'price', 'sale_price', 'sale_starts_at', 'sale_ends_at',
        'certificate_enabled', 'daily_release', 'lessons_per_day', 'is_published', 'is_featured', 'sort',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'certificate_enabled' => 'boolean',
        'daily_release' => 'boolean',
        'lessons_per_day' => 'integer',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // ===== العلاقات =====

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function topics()
    {
        return $this->hasMany(CourseTopic::class)->orderBy('sort')->orderBy('id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function liveSessions()
    {
        return $this->hasMany(LiveSession::class);
    }

    /** اسم مسار عرض عنصر المنهج حسب نوعه */
    public static function itemRoute(string $type): string
    {
        return ['lesson' => 'learn.lesson', 'quiz' => 'learn.quiz', 'live' => 'learn.live'][$type] ?? 'learn.lesson';
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->latest('id');
    }

    // ===== السعر والعرض =====

    public function getEffectivePriceAttribute(): float
    {
        $saleActive = (! $this->sale_starts_at || $this->sale_starts_at->isPast()) && (! $this->sale_ends_at || $this->sale_ends_at->isFuture());
        $sale = $this->sale_price !== null && $saleActive ? (float) $this->sale_price : null;

        return $sale !== null && $sale < (float) $this->price ? $sale : (float) $this->price;
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->effective_price <= 0;
    }

    public function getHasDiscountAttribute(): bool
    {
        return ! $this->is_free && $this->effective_price < (float) $this->price;
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->is_free ? 'مجانية' : number_format($this->effective_price, 0).' ر.س';
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }

    public function getRatingAttribute(): float
    {
        return round((float) $this->reviews()->where('is_approved', true)->avg('rating'), 1);
    }

    public function getLearnLinesAttribute(): array
    {
        return lines($this->what_you_learn);
    }

    public function getRequirementLinesAttribute(): array
    {
        return lines($this->requirements);
    }

    // ===== المنهج والتقدم =====

    /** الوحدات مع عناصرها (دروس + اختبارات) مرتبة؛ كل وحدة لها خاصية items */
    public function curriculum(): Collection
    {
        $topics = $this->topics()->with(['lessons', 'quizzes', 'liveSessions'])->get();
        foreach ($topics as $topic) {
            $items = $topic->lessons->map(fn ($l) => ['type' => 'lesson', 'model' => $l, 'sort' => $l->sort, 'order' => 0])
                ->concat($topic->quizzes->map(fn ($q) => ['type' => 'quiz', 'model' => $q, 'sort' => $q->sort, 'order' => 1]))
                ->concat($topic->liveSessions->map(fn ($s) => ['type' => 'live', 'model' => $s, 'sort' => $s->sort, 'order' => 2]))
                ->sortBy([['sort', 'asc'], ['order', 'asc'], [fn ($a, $b) => $a['model']->id <=> $b['model']->id]])
                ->values();
            $topic->setRelation('items', $items);
        }

        return $topics;
    }

    /** كل عناصر الدورة بالترتيب في قائمة واحدة */
    public function flatItems(?Collection $curriculum = null): Collection
    {
        return ($curriculum ?? $this->curriculum())->flatMap(fn ($topic) => $topic->items)->values();
    }

    /** @return array{done:int,total:int,percent:int,completed_lessons:array,passed_quizzes:array} */
    public function progressFor(?User $user): array
    {
        $total = $this->lessons()->count() + $this->quizzes()->count();
        if (! $user) {
            return ['done' => 0, 'total' => $total, 'percent' => 0, 'completed_lessons' => [], 'passed_quizzes' => []];
        }

        $lessons = LessonCompletion::where('user_id', $user->id)->where('course_id', $this->id)->pluck('lesson_id')->all();
        $quizzes = QuizAttempt::where('user_id', $user->id)->where('course_id', $this->id)->where('passed', true)
            ->distinct()->pluck('quiz_id')->all();
        $done = count($lessons) + count($quizzes);

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total ? (int) floor(min($done, $total) * 100 / $total) : 0,
            'completed_lessons' => $lessons,
            'passed_quizzes' => $quizzes,
        ];
    }

    /** أول عنصر لم يكمله الطالب (أو أول عنصر في الدورة) */
    public function nextItemFor(?User $user): ?array
    {
        $items = $this->flatItems();
        $progress = $this->progressFor($user);

        $items = $items->whereIn('type', ['lesson', 'quiz'])->values();

        return $items->first(fn ($item) => $item['type'] === 'lesson'
            ? ! in_array($item['model']->id, $progress['completed_lessons'])
            : ! in_array($item['model']->id, $progress['passed_quizzes'])) ?? $items->first();
    }

    /** عند اكتمال كل العناصر: تُعلَّم الدورة مكتملة وتُصدر الشهادة */
    public function syncCompletion(User $user): ?Certificate
    {
        $progress = $this->progressFor($user);
        if ($progress['total'] === 0 || $progress['done'] < $progress['total']) {
            return null;
        }

        $enrollment = $user->enrollmentFor($this);
        if ($enrollment && ! $enrollment->completed_at) {
            $enrollment->update(['completed_at' => now()]);
        }

        if (! $this->certificate_enabled) {
            return null;
        }

        return Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $this->id],
            ['code' => strtoupper(Str::random(4).'-'.Str::random(4)), 'issued_at' => now()]
        );
    }
}
