<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Instructor;
use Illuminate\Http\Request;

/** فهرس الدورات وصفحة تفاصيل الدورة */
class CourseController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->string('q')->toString());
        $category = CourseCategory::where('slug', $request->string('category')->toString())->first();
        $level = array_key_exists($request->string('level')->toString(), Course::LEVELS) ? $request->string('level')->toString() : null;
        $price = in_array($request->query('price'), ['free', 'paid'], true) ? $request->query('price') : null;

        $courses = Course::published()
            ->with(['instructor', 'category'])
            ->withCount(['lessons', 'enrollments'])
            ->withAvg(['reviews' => fn ($r) => $r->where('is_approved', true)], 'rating')
            ->when($category, fn ($w) => $w->where('category_id', $category->id))
            ->when($level, fn ($w) => $w->where('level', $level))
            ->when($price === 'free', fn ($w) => $w->where(fn ($p) => $p->whereNull('price')->orWhere('price', '<=', 0)->orWhere('sale_price', '<=', 0)))
            ->when($price === 'paid', fn ($w) => $w->where('price', '>', 0)->where(fn ($p) => $p->whereNull('sale_price')->orWhere('sale_price', '>', 0)))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('title', 'like', "%$q%")->orWhere('summary', 'like', "%$q%")))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        $categories = CourseCategory::whereHas('courses', fn ($c) => $c->published())->orderBy('sort')->get();

        return view('site.courses.index', compact('courses', 'categories', 'category', 'level', 'price', 'q'));
    }

    public function show(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);

        $user = $request->user();
        $curriculum = $course->curriculum();
        $enrollment = $user?->enrollmentFor($course);

        return view('site.courses.show', [
            'course' => $course->load(['instructor', 'category']),
            'curriculum' => $curriculum,
            'counts' => [
                'lessons' => $curriculum->sum(fn ($t) => $t->items->where('type', 'lesson')->count()),
                'quizzes' => $curriculum->sum(fn ($t) => $t->items->where('type', 'quiz')->count()),
                'students' => $course->enrollments()->where('status', 'active')->count(),
            ],
            'enrollment' => $enrollment,
            'progress' => $enrollment?->status === 'active' ? $course->progressFor($user) : null,
            'reviews' => $course->reviews()->where('is_approved', true)->with('user')->latest()->take(20)->get(),
            'myReview' => $user ? $course->reviews()->where('user_id', $user->id)->first() : null,
            'related' => Course::published()->whereKeyNot($course->id)
                ->when($course->category_id, fn ($w) => $w->where('category_id', $course->category_id))
                ->ordered()->take(3)->get(),
        ]);
    }

    public function instructor(Instructor $instructor)
    {
        return view('site.courses.instructor', [
            'instructor' => $instructor,
            'courses' => $instructor->courses()->published()->with('instructor')->withCount('lessons')->ordered()->get(),
        ]);
    }
}
