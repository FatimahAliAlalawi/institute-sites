<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Registration;
use App\Support\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** طلبات التسجيل = قائمة المتابعة (الخطوات 5 إلى 15 في الدليل التشغيلي للأفراد) */
class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $registrations = $this->filtered($request)->with('program')
            ->when($request->input('view') === 'due', fn ($q) => $q->orderBy('follow_up_at'))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'programs' => Program::orderBy('title')->get(['id', 'title']),
            'dueCount' => Registration::dueFollowUp()->count(),
            'seatInfo' => $request->filled('program') && $request->input('program') !== 'none'
                ? Program::withCount('seatRegistrations')->find($request->input('program'))
                : null,
        ]);
    }

    /** إضافة مهتم يدويًا (سأل عبر واتساب أو اتصال أو زيارة) */
    public function create(Request $request)
    {
        return view('admin.registrations.create', [
            'programs' => Program::active()->ordered()->get(['id', 'title', 'category']),
            'selected' => (int) $request->query('program'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:190',
            'program_id' => 'nullable|exists:programs,id',
            'source' => ['required', Rule::in(Registration::SOURCES)],
            'status' => ['required', Rule::in(Registration::OPEN_STATUSES)],
            'follow_up_at' => 'nullable|date',
            'admin_notes' => 'nullable|string|max:3000',
        ]);
        $data['phone'] = $this->normalizePhone($data['phone']);
        $data['follow_up_at'] ??= $this->defaultFollowUp($data['status']);

        $registration = Registration::create($data);

        return redirect()->route('admin.registrations.show', $registration)->with('status', 'تمت إضافة المهتم لقائمة المتابعة.');
    }

    public function show(Registration $registration)
    {
        $registration->load('program');
        $program = $registration->program ? Program::withCount('seatRegistrations')->find($registration->program_id) : null;

        return view('admin.registrations.show', compact('registration', 'program'));
    }

    public function update(Request $request, Registration $registration)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Registration::STATUSES))],
            'admin_notes' => 'nullable|string|max:3000',
            'source' => ['nullable', Rule::in(Registration::SOURCES)],
            'follow_up_at' => 'nullable|date',
            'invoice_no' => 'nullable|string|max:60',
            'attendance' => ['nullable', Rule::in(array_keys(Registration::ATTENDANCE))],
            'coupon_code' => 'nullable|string|max:60',
            'program_id' => 'nullable|exists:programs,id',
        ]);

        // اعتماد الفاتورة = تسجيل: كتابة رقم الفاتورة يضيف المتدرب للدورة تلقائيًا (بعد اختيار الدورة وتاريخ بدايتها)
        $autoAccepted = false;
        if (filled($data['invoice_no'] ?? null) && in_array($data['status'], Registration::OPEN_STATUSES, true)) {
            $data['status'] = 'accepted';
            $autoAccepted = true;
        }
        if (in_array($data['status'], Registration::SEAT_STATUSES, true)) {
            $target = ($data['program_id'] ?? $registration->program_id) ? Program::find($data['program_id'] ?? $registration->program_id) : null;
            if (! $target) {
                return back()->withInput()->withErrors(['program_id' => 'اختر الدورة أولًا حتى يُضاف المتدرب إليها.']);
            }
            if (! $target->start_date) {
                return back()->withInput()->withErrors(['program_id' => 'حدّد تاريخ بداية دورة «'.$target->title.'» من بيانات الدورة أولًا، ثم اعتمد الفاتورة.']);
            }
        }

        $becomesSeat = in_array($data['status'], Registration::SEAT_STATUSES, true)
            && ! in_array($registration->status, Registration::SEAT_STATUSES, true);
        $programId = $data['program_id'] ?? $registration->program_id;
        $program = $programId ? Program::find($programId) : null;

        // حماية المقاعد: لا قبول فوق عدد المقاعد
        if ($becomesSeat && $program && $program->seats && $program->seatRegistrations()->count() >= $program->seats) {
            return back()->withInput()->withErrors(['status' => 'الدورة مكتملة: '.$program->seats.' من '.$program->seats.' مقعدًا محجوزة. ارفع الطلب للمشرف أو سجّله في دورة قادمة.']);
        }

        // متى المتابعة القادمة؟ تلقائيًا بعد يومين عند إرسال العرض، وتُمسح عند إغلاق الطلب
        if (! in_array($data['status'], Registration::OPEN_STATUSES, true)) {
            $data['follow_up_at'] = null;
        } elseif (empty($data['follow_up_at']) && $data['status'] !== $registration->status) {
            $data['follow_up_at'] = $this->defaultFollowUp($data['status']);
        }

        $registration->update($data);

        // بعد الدفع: رابط إكمال البيانات بالإيميل تلقائيًا
        $profileSent = $becomesSeat && \App\Support\ProfileRequests::send($registration->fresh('program'));

        $msg = $autoAccepted
            ? 'تم اعتماد الفاتورة وإضافة '.($registration->name ?: 'المتقدم').' لدورة «'.$program?->title.'» التي تبدأ '.$program?->start_date?->format('Y-m-d').'.'
            : 'تم حفظ المتابعة.';
        if ($profileSent) {
            $msg .= ' أُرسل له رابط إكمال البيانات بالإيميل.';
        } elseif ($becomesSeat && ! $registration->profile_done_at) {
            $msg .= ' أرسل له رابط إكمال البيانات واتساب من صفحة الطلب.';
        }
        if ($becomesSeat && $program && $program->closeIfFull()) {
            $msg .= ' اكتملت مقاعد «'.$program->title.'» فأُغلق التسجيل فيها تلقائيًا.';
        }

        return back()->with('status', $msg);
    }

    public function destroy(Registration $registration)
    {
        $registration->delete();

        return redirect()->route('admin.registrations.index')->with('status', 'تم حذف الطلب.');
    }

    /** تصدير الطلبات (بنفس الفلاتر الحالية) إلى ملف CSV يفتح في Excel بالعربي */
    public function export(Request $request)
    {
        $rows = $this->filtered($request)->with('program')->latest()->get();
        $filename = 'registrations-'.now()->format('Y-m-d').'.csv';

        $extraColumns = FormBuilder::extraColumns('registration');

        return response()->streamDownload(function () use ($rows, $extraColumns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['#', 'التاريخ', 'الاسم', 'الجوال', 'الهوية', 'تاريخ الميلاد', 'البريد', 'المدينة', 'الجنس', 'المؤهل', 'الحالة الوظيفية', 'البرنامج', 'الحالة', 'المصدر', 'موعد المتابعة', 'رقم الفاتورة', 'تأكيد الحضور', 'كود الخصم المرسل', 'ملاحظات المتقدم', 'ملاحظات الإدارة'], array_values($extraColumns)));
            foreach ($rows as $r) {
                fputcsv($out, array_merge([
                    $r->id, $r->created_at->format('Y-m-d H:i'), $r->name, $r->phone, $r->national_id, $r->birth_date?->format('Y-m-d'), $r->email, $r->city, $r->gender,
                    $r->qualification, $r->employment_status, $r->program?->title ?? 'تسجيل عام', $r->status_label,
                    $r->source, $r->follow_up_at?->format('Y-m-d'), $r->invoice_no, $r->attendance_label, $r->coupon_code,
                    $r->notes, $r->admin_notes,
                ], array_map(fn ($key) => FormBuilder::plain($r->extra[$key] ?? ''), array_keys($extraColumns))));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function filtered(Request $request)
    {
        $q = trim((string) $request->input('q'));

        return Registration::query()
            ->when($request->input('view') === 'due', fn ($w) => $w->dueFollowUp())
            ->when($request->filled('status'), fn ($w) => $w->where('status', $request->input('status')))
            ->when($request->filled('source'), fn ($w) => $w->where('source', $request->input('source')))
            ->when($request->filled('program'), fn ($w) => $request->input('program') === 'none'
                ? $w->whereNull('program_id')
                : $w->where('program_id', $request->input('program')))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('national_id', 'like', "%$q%")->orWhere('email', 'like', "%$q%")));
    }

    protected function defaultFollowUp(string $status): ?string
    {
        return match ($status) {
            'new' => now()->toDateString(),
            'contacted' => now()->addDays(2)->toDateString(),
            default => null,
        };
    }

    /** الجوال بصيغة 05XXXXXXXX إن أمكن */
    protected function normalizePhone(string $phone): string
    {
        return Registration::normalizePhone($phone);
    }

    /** @deprecated بقيت للتوافق */
    protected function normalizePhoneLegacy(string $phone): string
    {
        $digits = preg_replace('/\D/', '', strtr($phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
        if (str_starts_with($digits, '9665')) {
            $digits = '0'.substr($digits, 3);
        } elseif (str_starts_with($digits, '5') && strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return $digits ?: $phone;
    }
}
