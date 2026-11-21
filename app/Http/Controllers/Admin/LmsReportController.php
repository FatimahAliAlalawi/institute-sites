<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Review;
use Illuminate\Http\Request;

/** نتائج الاختبارات، الشهادات، التقييمات */
class LmsReportController extends Controller
{
    public function attempts(Request $request)
    {
        return view('admin.lms.attempts', [
            'attempts' => QuizAttempt::with(['user', 'quiz', 'course'])
                ->when($request->filled('course'), fn ($w) => $w->where('course_id', $request->input('course')))
                ->when($request->filled('result'), fn ($w) => $w->where('passed', $request->input('result') === 'passed'))
                ->latest()->paginate(30)->withQueryString(),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function certificates(Request $request)
    {
        $q = strtoupper(trim((string) $request->input('q')));

        return view('admin.lms.certificates', [
            'certificates' => Certificate::with(['user', 'course'])
                ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('code', 'like', "%$q%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.trim((string) $request->input('q')).'%'))))
                ->latest('issued_at')->paginate(30)->withQueryString(),
        ]);
    }

    public function destroyCertificate(Certificate $certificate)
    {
        $certificate->delete();

        return back()->with('status', 'تم إلغاء الشهادة.');
    }

    public function reviews()
    {
        return view('admin.lms.reviews', ['reviews' => Review::with(['user', 'course'])->latest()->paginate(30)]);
    }

    public function updateReview(Request $request, Review $review)
    {
        $review->update(['is_approved' => $request->boolean('is_approved')]);

        return back()->with('status', $review->is_approved ? 'تم إظهار التقييم.' : 'تم إخفاء التقييم.');
    }

    public function destroyReview(Review $review)
    {
        $review->delete();

        return back()->with('status', 'تم حذف التقييم.');
    }
}
