<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\Post;
use App\Models\Program;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $admin = Admin::with('role')->find($request->session()->get('admin_id'));

        // كل بطاقة تظهر فقط لمن يملك صلاحية قسمها
        $stats = collect([
            ['enrollments', 'اشتراكات بانتظار تأكيد الدفع', fn () => Enrollment::where('status', 'pending')->count(), route('admin.enrollments.index', ['status' => 'pending'])],
            ['students', 'الطلاب', fn () => User::count(), route('admin.students.index')],
            ['courses', 'الدورات المنشورة', fn () => Course::published()->count(), route('admin.courses.index')],
            ['lms_reports', 'الشهادات الصادرة', fn () => Certificate::count(), route('admin.certificates.index')],
            ['registrations', 'طلبات تسجيل جديدة', fn () => Registration::where('status', 'new')->count(), route('admin.registrations.index', ['status' => 'new'])],
            ['registrations', 'إجمالي طلبات التسجيل', fn () => Registration::count(), route('admin.registrations.index')],
            ['messages', 'رسائل غير مقروءة', fn () => Message::where('is_read', false)->count(), route('admin.messages.index')],
            ['programs', 'البرامج المنشورة', fn () => Program::active()->count(), route('admin.programs.index')],
            ['posts', 'الأخبار', fn () => Post::count(), route('admin.posts.index')],
        ])->filter(fn ($s) => $admin->allows($s[0]))
            ->map(fn ($s) => ['label' => $s[1], 'value' => ($s[2])(), 'link' => $s[3]])
            ->values()->all();

        return view('admin.dashboard', [
            'stats' => $stats,
            'latestRegistrations' => $admin->allows('registrations') ? Registration::with('program')->latest()->take(8)->get() : collect(),
            'latestMessages' => $admin->allows('messages') ? Message::latest()->take(5)->get() : collect(),
            'byProgram' => $admin->allows('registrations') ? Program::withCount('registrations')->orderByDesc('registrations_count')->take(8)->get() : collect(),
        ]);
    }
}
