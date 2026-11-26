<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Support\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** إكمال بيانات المتدرب بعد الدفع، برابط شخصي سري (بدون حساب) */
class ProfileController extends Controller
{
    public function show(string $token)
    {
        $registration = $this->resolve($token);

        return response()->view('site.profile-complete', ['registration' => $registration, 'token' => $token])
            ->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'same-origin');
    }

    public function store(Request $request, string $token)
    {
        $registration = $this->resolve($token);
        foreach (['national_id', 'phone'] as $k) {
            if (is_string($request->input($k))) {
                $request->merge([$k => FormBuilder::latinDigits($request->input($k))]);
            }
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', FormBuilder::fourPartName()],
            'national_id' => ['required', 'regex:/^[12][0-9]{9}$/'],
            'gender' => ['required', Rule::in(['ذكر', 'أنثى'])],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s\-]{9,20}$/'],
            'email' => ['required', 'email', 'max:190'],
            'birth_date' => ['required', 'date', 'before:'.now()->subYears(10)->toDateString(), 'after:1930-01-01'],
        ], [
            'national_id.regex' => 'رقم الهوية أو الإقامة 10 أرقام ويبدأ بـ 1 أو 2.',
            'phone.regex' => 'اكتب رقم الجوال بشكل صحيح، مثل 05xxxxxxxx.',
            'birth_date.before' => 'تأكد من تاريخ الميلاد.',
            'birth_date.after' => 'تأكد من تاريخ الميلاد.',
        ], [
            'name' => 'الاسم الرباعي', 'national_id' => 'رقم الهوية', 'gender' => 'الجنس', 'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني', 'birth_date' => 'تاريخ الميلاد',
        ]);
        $data['name'] = trim(preg_replace('/\s+/u', ' ', $data['name']));
        $data['phone'] = Registration::normalizePhone($data['phone']);
        $data['email'] = mb_strtolower(trim($data['email']));
        $registration->forceFill($data + ['profile_done_at' => now()])->save();

        return redirect()->route('profile.complete', $token)->with('saved', true);
    }

    /** الرابط يعمل للمتدرب المسجّل (دفع) فقط */
    protected function resolve(string $token): Registration
    {
        $registration = Registration::with('program')->where('test_token', $token)
            ->whereIn('status', Registration::SEAT_STATUSES)->first();
        abort_unless($registration, 404);

        return $registration;
    }
}
