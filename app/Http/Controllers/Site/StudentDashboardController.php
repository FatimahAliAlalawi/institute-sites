<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\DailyRelease;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** لوحة الطالب: دوراتي، شهاداتي، نتائج الاختبارات، الملف الشخصي */
class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // اشتراكات جديدة من الموقع القديم تظهر فورًا
        \App\Support\TutorSync::forEmail($user->email);
        $enrollments = $user->enrollments()->with('course.instructor')->latest()->get()
            ->filter(fn ($e) => $e->course)
            ->each(function ($e) use ($user) {
                $e->progress = $e->course->progressFor($user);
                if ($e->status === 'active' && $e->course->daily_release) {
                    $e->release = DailyRelease::state($e->course, $user, null, $e->progress);
                    $e->attendance = DailyRelease::summary($e);
                }
            });

        $upcoming = \App\Models\LiveSession::with('course')
            ->whereIn('course_id', $enrollments->where('status', 'active')->pluck('course_id'))
            ->where('starts_at', '>=', now()->subHours(3))->where('starts_at', '<=', now()->addDays(14))
            ->orderBy('starts_at')->get()->filter(fn ($s) => $s->state !== 'ended')->take(5);

        return view('site.dashboard.index', [
            'upcoming' => $upcoming,
            'enrollments' => $enrollments,
            'stats' => [
                'active' => $enrollments->where('status', 'active')->count(),
                'completed' => $enrollments->whereNotNull('completed_at')->count(),
                'certificates' => $user->certificates()->count(),
            ],
        ]);
    }

    public function certificates(Request $request)
    {
        return view('site.dashboard.certificates', [
            'certificates' => $request->user()->certificates()->with('course')->latest('issued_at')->get(),
        ]);
    }

    public function attempts(Request $request)
    {
        return view('site.dashboard.attempts', [
            'attempts' => $request->user()->quizAttempts()->with(['quiz', 'course'])->latest()->paginate(20),
        ]);
    }

    public function orders(Request $request)
    {
        return view('site.dashboard.orders', ['orders' => \App\Models\Order::where('user_id', $request->user()->id)->latest('id')->paginate(20)]);
    }

    /** طالب ناقصة بياناته الإلزامية (مثل المنقولين من الموقع القديم) */
    public function completeProfile(Request $request)
    {
        $fields = \App\Support\ProfileCompletion::missing($request->user());
        if ($fields->isEmpty()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('site.dashboard.complete', ['user' => $request->user(), 'fields' => $fields]);
    }

    public function saveCompleteProfile(Request $request)
    {
        $user = $request->user();
        $fields = \App\Support\ProfileCompletion::missing($user);
        $rules = $attributes = [];
        foreach ($fields as $field) {
            if (in_array($field->type, ['national_id', 'tel', 'number'], true) && is_string($request->input($field->key))) {
                $request->merge([$field->key => \App\Support\FormBuilder::latinDigits($request->input($field->key))]);
            }
            $rules[$field->key] = array_merge(['required'], \App\Support\FormBuilder::typeRules($field), match ($field->key) {
                'name' => array_filter(['max:150', \App\Support\ProfileCompletion::wantsFourPart($field) ? \App\Support\FormBuilder::fourPartName() : null]),
                'national_id' => [Rule::unique('users', 'national_id')->ignore($user->id)],
                'birth_date' => ['before:today', 'after:1920-01-01'],
                default => [],
            });
            $attributes[$field->key] = $field->label;
        }
        $data = $request->validate($rules, [
            'national_id.unique' => 'رقم الهوية مسجّل بحساب آخر — تواصل معنا.',
            'national_id.regex' => 'رقم الهوية/الإقامة يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
        ], $attributes);

        $user->forceFill($data)->save();

        return redirect()->intended(route('dashboard'))->with('status', 'تم حفظ بياناتك، شكرًا لك.');
    }

    public function profile()
    {
        return view('site.dashboard.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s\-]{9,20}$/'],
            'national_id' => ['nullable', 'regex:/^[12][0-9]{9}$/', Rule::unique('users', 'national_id')->ignore($user->id)],
            'birth_date' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(\App\Models\User::GENDERS)],
            'qualification' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ], ['national_id.regex' => 'رقم الهوية/الإقامة 10 أرقام ويبدأ بـ 1 أو 2.'], ['avatar' => 'الصورة الشخصية', 'password' => 'كلمة المرور الجديدة', 'national_id' => 'رقم الهوية', 'birth_date' => 'تاريخ الميلاد', 'gender' => 'الجنس', 'qualification' => 'المؤهل']);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
            }
            $user->password = $data['password'];
        }

        $user->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null]);
        foreach (['national_id', 'birth_date', 'gender', 'qualification'] as $key) {
            if ($request->has($key)) {
                $user->{$key} = $data[$key] ?? null;
            }
        }
        $user->avatar = Uploader::replace($request->file('avatar'), $user->avatar, 'avatars', $request->boolean('remove_avatar'));
        $user->save();

        return back()->with('status', 'تم حفظ بياناتك.');
    }
}
