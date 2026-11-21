<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Instructor;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $courses = Course::with(['category', 'instructor'])
            ->withCount(['lessons', 'quizzes', 'enrollments as students_count' => fn ($e) => $e->where('status', 'active')])
            ->when($q !== '', fn ($w) => $w->where('title', 'like', "%$q%"))
            ->when($request->filled('category'), fn ($w) => $w->where('category_id', $request->input('category')))
            ->ordered()->paginate(20)->withQueryString();

        return view('admin.courses.index', ['courses' => $courses, 'categories' => CourseCategory::orderBy('sort')->get()]);
    }

    public function create()
    {
        return view('admin.courses.form', $this->formData(new Course(['level' => 'all', 'certificate_enabled' => true, 'is_published' => false])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['thumbnail'] = $request->hasFile('thumbnail') ? Uploader::store($request->file('thumbnail'), 'courses') : null;
        $course = Course::create($data);

        return redirect()->route('admin.courses.curriculum', $course)->with('status', 'تم إنشاء الدورة ✅ أضف الآن وحدات ودروس الدورة.');
    }

    public function edit(Course $course)
    {
        return view('admin.courses.form', $this->formData($course));
    }

    public function update(Request $request, Course $course)
    {
        $data = $this->validated($request, $course);
        $data['thumbnail'] = Uploader::replace($request->file('thumbnail'), $course->thumbnail, 'courses', $request->boolean('remove_thumbnail'));
        $course->update($data);

        return redirect()->route('admin.courses.edit', $course)->with('status', 'تم حفظ التعديلات.');
    }

    public function destroy(Course $course)
    {
        foreach ($course->lessons as $lesson) {
            Uploader::delete($lesson->attachment);
        }
        Uploader::delete($course->thumbnail);
        $course->delete();

        return redirect()->route('admin.courses.index')->with('status', 'تم حذف الدورة وكل محتواها.');
    }

    protected function formData(Course $course): array
    {
        return [
            'course' => $course,
            'categories' => CourseCategory::orderBy('sort')->get(),
            'instructors' => Instructor::orderBy('sort')->get(),
        ];
    }

    protected function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('courses', 'slug')->ignore($course?->id)],
            'category_id' => 'nullable|exists:course_categories,id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'summary' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'what_you_learn' => 'nullable|string|max:5000',
            'requirements' => 'nullable|string|max:5000',
            'target_audience' => 'nullable|string|max:190',
            'level' => ['required', Rule::in(array_keys(Course::LEVELS))],
            'duration_text' => 'nullable|string|max:100',
            'intro_video' => 'nullable|url|max:255',
            'price' => 'nullable|numeric|min:0|max:9999999',
            'sale_price' => 'nullable|numeric|min:0|max:9999999',
            'sale_starts_at' => 'nullable|date',
            'sale_ends_at' => 'nullable|date|after_or_equal:sale_starts_at',
            'sort' => 'nullable|integer',
            'lessons_per_day' => 'nullable|integer|min:1|max:20',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [], ['thumbnail' => 'صورة الدورة', 'sale_price' => 'سعر الخصم', 'intro_video' => 'الفيديو التعريفي']);
        unset($data['thumbnail']);

        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['lessons_per_day'] = max(1, (int) ($data['lessons_per_day'] ?? 1));
        foreach (['certificate_enabled', 'daily_release', 'is_published', 'is_featured'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
