<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الاشتراكات والمدفوعات: مراجعة إيصالات التحويل وتفعيل/إلغاء الاشتراك */
class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $enrollments = Enrollment::with(['user', 'course'])
            ->when($request->filled('status'), fn ($w) => $w->where('status', $request->input('status')))
            ->when($request->filled('course'), fn ($w) => $w->where('course_id', $request->input('course')))
            ->when($q !== '', fn ($w) => $w->whereHas('user', fn ($u) => $u->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.enrollments.index', [
            'enrollments' => $enrollments,
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'revenue' => Enrollment::where('status', 'active')->where('payment_method', 'bank_transfer')->sum('amount'),
        ]);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Enrollment::STATUSES))]]);
        $enrollment->update($data + ['approved_at' => $data['status'] === 'active' ? ($enrollment->approved_at ?? now()) : $enrollment->approved_at]);
        if ($enrollment->order && $enrollment->order->status !== 'paid' && $data['status'] === 'active') {
            $enrollment->order->markPaid();
        } elseif ($enrollment->order && $data['status'] === 'cancelled' && $enrollment->order->status === 'pending') {
            $enrollment->order->update(['status' => 'cancelled']);
        }

        return back()->with('status', $data['status'] === 'active' ? 'تم تفعيل الاشتراك ✅' : 'تم تحديث حالة الاشتراك.');
    }

    public function destroy(Enrollment $enrollment)
    {
        Uploader::delete($enrollment->receipt);
        $enrollment->delete();

        return back()->with('status', 'تم حذف الاشتراك.');
    }

    public function receipt(Enrollment $enrollment)
    {
        $path = $enrollment->receipt ? Uploader::privatePath($enrollment->receipt) : null;
        abort_unless($path, 404);

        return response()->file($path);
    }
}
