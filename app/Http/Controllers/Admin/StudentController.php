<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\DailyRelease;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** إدارة الطلاب: القائمة، الملف، الاشتراك اليدوي، إعادة تعيين كلمة المرور */
class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $students = User::withCount(['enrollments as courses_count' => fn ($e) => $e->where('status', 'active'), 'certificates'])
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('national_id', 'like', "%$q%")))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    /** تصدير الطلاب إلى Excel */
    public function export()
    {
        $rows = User::withCount(['enrollments as courses_count' => fn ($e) => $e->where('status', 'active'), 'certificates'])->latest()->get();
        $extra = \App\Support\FormBuilder::extraColumns('signup');

        return response()->streamDownload(function () use ($rows, $extra) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['#', 'الاسم', 'رقم الهوية', 'تاريخ الميلاد', 'الجنس', 'المؤهل', 'الجوال', 'البريد', 'الدورات', 'الشهادات', 'تاريخ التسجيل', 'الحالة'], array_values($extra)));
            foreach ($rows as $u) {
                fputcsv($out, array_merge([
                    $u->id, $u->name, $u->national_id, $u->birth_date?->format('Y-m-d'), $u->gender, $u->qualification, $u->phone, $u->email,
                    $u->courses_count, $u->certificates_count, $u->created_at->format('Y-m-d'), $u->is_active ? 'فعّال' : 'موقوف',
                ], array_map(fn ($key) => \App\Support\FormBuilder::plain($u->extra[$key] ?? ''), array_keys($extra))));
            }
            fclose($out);
        }, 'students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'national_id' => ['nullable', 'regex:/^[12][0-9]{9}$/', 'unique:users,national_id'],
            'birth_date' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(User::GENDERS)],
            'qualification' => 'nullable|string|max:100',
            'password' => 'required|string|min:8',
        ], ['national_id.regex' => 'رقم الهوية/الإقامة 10 أرقام ويبدأ بـ 1 أو 2.'], ['national_id' => 'رقم الهوية', 'birth_date' => 'تاريخ الميلاد']);
        $student = User::create($data);

        return redirect()->route('admin.students.show', $student)->with('status', 'تم إنشاء حساب الطالب.');
    }

    public function show(User $student)
    {
        $enrollments = $student->enrollments()->with('course')->latest()->get()
            ->filter(fn ($e) => $e->course)
            ->each(fn ($e) => $e->progress = $e->course->progressFor($student));

        return view('admin.students.show', [
            'student' => $student,
            'enrollments' => $enrollments,
            'attempts' => $student->quizAttempts()->with('quiz')->latest()->take(20)->get(),
            'certificates' => $student->certificates()->with('course')->get(),
            'courses' => Course::whereNotIn('id', $enrollments->pluck('course_id'))->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, User $student)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => 'nullable|string|max:20',
            'national_id' => ['nullable', 'regex:/^[12][0-9]{9}$/', Rule::unique('users', 'national_id')->ignore($student->id)],
            'birth_date' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(User::GENDERS)],
            'qualification' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8',
            'study_days' => 'nullable|array',
            'study_days.*' => 'integer|min:0|max:6',
        ], ['national_id.regex' => 'رقم الهوية/الإقامة 10 أرقام ويبدأ بـ 1 أو 2.'], ['password' => 'كلمة المرور الجديدة', 'study_days' => 'أيام الدراسة', 'national_id' => 'رقم الهوية', 'birth_date' => 'تاريخ الميلاد']);

        $student->fill([
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'is_active' => $request->boolean('is_active'),
            'national_id' => $data['national_id'] ?? null, 'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null, 'qualification' => $data['qualification'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $student->password = $data['password'];
        }
        $student->study_days = $request->input('days_mode') === 'custom' && ! empty($data['study_days'])
            ? implode(',', DailyRelease::parse(implode(',', $data['study_days'])))
            : null;
        $student->save();

        return back()->with('status', 'تم حفظ بيانات الطالب.');
    }

    public function destroy(User $student)
    {
        foreach ($student->enrollments as $enrollment) {
            Uploader::delete($enrollment->receipt);
        }
        Uploader::delete($student->avatar);
        $student->delete();

        return redirect()->route('admin.students.index')->with('status', 'تم حذف الطالب وكل بياناته.');
    }

    /** تحديد أيام الدراسة لمجموعة متدربين دفعة واحدة */
    public function bulkDays(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'days_mode' => 'required|in:global,custom',
            'study_days' => 'nullable|array',
            'study_days.*' => 'integer|min:0|max:6',
        ], ['ids.required' => 'اختر متدربًا واحدًا على الأقل من الجدول.']);

        $days = $data['days_mode'] === 'custom' && ! empty($data['study_days'])
            ? implode(',', DailyRelease::parse(implode(',', $data['study_days'])))
            : null;
        $count = User::whereIn('id', $data['ids'])->update(['study_days' => $days]);

        return back()->with('status', 'تم تحديث أيام الدراسة لـ '.$count.' متدرب: '.($days ? DailyRelease::label(DailyRelease::parse($days)) : 'الإعداد العام'));
    }

    /** اشتراك يدوي (مثلًا: دفع نقدي في المعهد أو منحة) */
    public function enroll(Request $request, User $student)
    {
        $data = $request->validate(['course_id' => 'required|exists:courses,id'], [], ['course_id' => 'الدورة']);
        Enrollment::updateOrCreate(
            ['user_id' => $student->id, 'course_id' => $data['course_id']],
            ['status' => 'active', 'payment_method' => 'manual', 'approved_at' => now()]
        );

        return back()->with('status', 'تم تسجيل الطالب في الدورة.');
    }
}
