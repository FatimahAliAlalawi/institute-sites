<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Program;
use App\Support\QrCode;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $programs = Program::withCount(['registrations', 'seatRegistrations'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('plan'), fn ($q) => $q->where('plan_status', $request->input('plan')))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.programs.index', compact('programs'));
    }

    public function create()
    {
        return view('admin.programs.form', ['program' => new Program(['category' => 'youth', 'mode' => 'حضوري', 'registration_open' => true, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image'] = $request->hasFile('image') ? Uploader::store($request->file('image'), 'programs') : null;
        $program = new Program($data);
        $this->applyApprovals($request, $program);
        $program->save();

        return redirect()->route('admin.programs.index')->with('status', 'تمت إضافة البرنامج.');
    }

    public function edit(Program $program)
    {
        return view('admin.programs.form', compact('program'));
    }

    public function update(Request $request, Program $program)
    {
        $data = $this->validated($request, $program);
        $data['image'] = Uploader::replace($request->file('image'), $program->image, 'programs', $request->boolean('remove_image'));
        $program->fill($data);
        $reset = $this->applyApprovals($request, $program);
        $program->save();

        return redirect()->route('admin.programs.edit', $program)->with('status', 'تم حفظ التعديلات.'
            .($reset ? ' تغيّرت بيانات سبق اعتمادها، فعادت للإدارة لإعادة الاعتماد.' : ''));
    }

    /** بطاقة الإعلان: كل بيانات الإعلان المعتمدة في مكان واحد + رموز QR، جاهزة للمصمم وللطباعة */
    public function adCard(Program $program)
    {
        return view('admin.programs.ad-card', [
            'program' => $program,
            'qrPage' => QrCode::svg(route('programs.short', $program->id), 6, setting('color_primary', '#32255D')),
            'qrWa' => $program->whatsapp_url ? QrCode::svg(route('programs.whatsapp', $program->id), 6, '#0b7a5e') : null,
        ]);
    }

    /** تنزيل رمز QR بصيغة SVG (page = صفحة البرنامج والتسجيل، wa = التواصل عبر واتساب) */
    public function qr(Program $program, string $type)
    {
        $url = $type === 'wa' ? route('programs.whatsapp', $program->id) : route('programs.short', $program->id);

        return response(QrCode::svg($url, 16), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.$type.'-'.$program->id.'.svg"',
        ]);
    }

    /**
     * حالة اعتماد الخطة والإعلان:
     * - من يملك صلاحية "اعتماد الخطة التدريبية والإعلانات" يختار الحالة مباشرة.
     * - غيره يدرج البرنامج أو يرسله للاعتماد فقط، وأي تعديل على بيانات معتمدة يعيدها "بانتظار الاعتماد".
     * يرجع true إذا أُعيد شيء معتمد للانتظار.
     */
    protected function applyApprovals(Request $request, Program $program): bool
    {
        $admin = Admin::with('role')->find($request->session()->get('admin_id'));
        $approver = $admin?->allows('approvals') ?? false;
        $reset = false;

        foreach (['plan' => Program::PLAN_FIELDS, 'ad' => Program::AD_FIELDS] as $kind => $fields) {
            $column = $kind.'_status';
            $current = $program->getOriginal($column) ?: 'none';
            $wanted = (string) $request->input($column, $current);
            $wanted = array_key_exists($wanted, Program::APPROVAL_STATUSES) ? $wanted : 'none';

            if (! $approver) {
                // غير المعتمِد لا يعتمد ولا يرفض بنفسه
                if (in_array($wanted, ['approved', 'rejected'], true) && $wanted !== $current) {
                    $wanted = $current;
                }
                if ($current === 'approved' && $wanted === 'approved' && $program->exists && $program->isDirty($fields)) {
                    $wanted = 'pending';
                    $reset = true;
                }
            }

            $program->{$column} = $wanted;
            if ($wanted === 'approved' && $current !== 'approved') {
                $program->{$kind.'_approved_by'} = $admin?->name ?: $admin?->username;
                $program->{$kind.'_approved_at'} = now();
            } elseif ($wanted !== 'approved') {
                $program->{$kind.'_approved_by'} = null;
                $program->{$kind.'_approved_at'} = null;
            }
        }

        if ($approver && $request->has('approval_note')) {
            $program->approval_note = $request->input('approval_note') ?: null;
        }

        return $reset;
    }

    public function destroy(Program $program)
    {
        Uploader::delete($program->image);
        $program->delete();

        return redirect()->route('admin.programs.index')->with('status', 'تم حذف البرنامج.');
    }

    protected function validated(Request $request, ?Program $program = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('programs', 'slug')->ignore($program?->id)],
            'category' => ['required', Rule::in(array_keys(Program::CATEGORIES))],
            'summary' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'goal' => 'nullable|string|max:2000',
            'target_audience' => 'nullable|string|max:190',
            'duration' => 'nullable|string|max:190',
            'schedule_days' => 'nullable|string|max:120',
            'schedule_time' => 'nullable|string|max:80',
            'trainer_name' => 'nullable|string|max:120',
            'hours' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:190',
            'mode' => ['required', Rule::in(Program::MODES)],
            'seats' => 'nullable|integer|min:0|max:100000',
            'price' => 'nullable|numeric|min:0|max:9999999',
            'sale_price' => 'nullable|numeric|min:0|max:9999999',
            'start_date' => 'nullable|date',
            'offer_ends_at' => 'nullable|date',
            'contact_phone' => 'nullable|string|max:30',
            'accreditation_no' => 'nullable|string|max:100',
            'approval_note' => 'nullable|string|max:1000',
            'certificate' => 'nullable|string|max:190',
            'steps' => 'nullable|string|max:5000',
            'outcomes' => 'nullable|string|max:5000',
            'external_link' => 'nullable|url|max:255',
            'sort' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        unset($data['image'], $data['approval_note']);
        if (isset($data['sale_price'], $data['price']) && (float) $data['sale_price'] >= (float) $data['price']) {
            $data['sale_price'] = null; // سعر الخصم يجب أن يكون أقل من الرسوم
        }

        $data['sort'] = (int) ($data['sort'] ?? 0);
        foreach (['registration_open', 'is_featured', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
