<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\User;
use App\Support\FormBuilder;
use App\Support\LegacyPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/** تسجيل دخول الطلاب وإنشاء الحسابات */
class StudentAuthController extends Controller
{
    public function showLogin()
    {
        return view('site.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string|max:200',
        ]);

        $key = 'student-login|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'محاولات كثيرة، حاول بعد '.RateLimiter::availableIn($key).' ثانية.']);
        }

        $email = mb_strtolower(trim($data['email']));
        // طالب سجّل أو اشترك في الموقع القديم: يُجلب حسابه واشتراكاته فورًا
        $local = User::where('email', $email)->first();
        if (! $local || $local->legacy_password) {
            \App\Support\TutorSync::forEmail($email);
        }
        if (! Auth::attempt(['email' => $email, 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))
            && ! $this->attemptLegacy($email, $data['password'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 300);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    /** الطلاب المستوردون من ووردبريس: يدخلون بكلمة مرورهم القديمة، وتُحوَّل فورًا للتشفير الجديد */
    protected function attemptLegacy(string $email, string $password, bool $remember): bool
    {
        $user = User::where('email', $email)->where('is_active', true)->whereNotNull('legacy_password')->first();
        if (! $user || ! LegacyPassword::check($password, $user->legacy_password)) {
            return false;
        }

        $user->forceFill(['password' => $password, 'legacy_password' => null])->save();
        Auth::login($user, $remember);

        return true;
    }

    public function showSignup()
    {
        return view('site.auth.signup', ['fields' => FormField::for('signup')]);
    }

    public function signup(Request $request)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('home');
        }

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $nameField = FormField::for('signup')->firstWhere('key', 'name');
        [$data, $extra] = FormBuilder::validate($request, 'signup', [
            'name' => array_filter(['max:150', $nameField && str_contains($nameField->label, 'رباعي') ? FormBuilder::fourPartName() : null]),
            'email' => ['unique:users,email'],
            'national_id' => ['unique:users,national_id'],
            'birth_date' => ['before:today', 'after:1920-01-01'],
            'password' => ['string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'هذا البريد مسجّل مسبقًا، سجّل دخولك بدلًا من ذلك.',
            'national_id.unique' => 'رقم الهوية مسجّل مسبقًا بحساب آخر — سجّل دخولك أو تواصل معنا.',
            'national_id.regex' => 'رقم الهوية/الإقامة يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
        ]);

        $user = User::create(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'password', 'national_id', 'birth_date', 'gender', 'qualification'])));
        if ($extra) {
            $user->forceFill(['extra' => $extra])->save();
        }
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('status', 'أهلًا '.$user->name.'! تم إنشاء حسابك بنجاح.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
