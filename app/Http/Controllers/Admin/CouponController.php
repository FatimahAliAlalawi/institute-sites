<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** كوبونات الخصم */
class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::withSum(['orders as revenue' => fn ($o) => $o->where('status', 'paid')], 'total')
                ->withSum(['orders as discounts' => fn ($o) => $o->where('status', 'paid')], 'discount')
                ->latest('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['type' => 'percent', 'is_active' => true, 'per_user_limit' => 1, 'code' => strtoupper(Str::random(8))]), 'courses' => $this->courses()]);
    }

    public function store(Request $request)
    {
        $coupon = Coupon::create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('status', 'تم إنشاء الكوبون '.$coupon->code.'.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', ['coupon' => $coupon, 'courses' => $this->courses()]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('status', 'تم حفظ الكوبون '.$coupon->code.'.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('status', 'تم حذف الكوبون (الطلبات السابقة تحتفظ بالكود).');
    }

    protected function courses()
    {
        return Course::where('price', '>', 0)->orderBy('title')->get(['id', 'title']);
    }

    protected function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_\-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'description' => 'nullable|string|max:190',
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'value' => ['required', 'numeric', 'min:0.01', $request->input('type') === 'percent' ? 'max:100' : 'max:9999999'],
            'max_discount' => 'nullable|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
        ], ['code.regex' => 'الكود بالأحرف الإنجليزية والأرقام فقط (بدون مسافات).'], [
            'code' => 'الكود', 'value' => 'قيمة الخصم', 'expires_at' => 'تاريخ الانتهاء', 'starts_at' => 'تاريخ البدء',
        ]);
        $data['course_ids'] = ! empty($data['course_ids']) ? array_values(array_map('intval', $data['course_ids'])) : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
