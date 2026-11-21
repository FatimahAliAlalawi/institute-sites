<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:200',
        ]);

        $key = 'admin-login|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'محاولات كثيرة جدًا، حاول بعد '.RateLimiter::availableIn($key).' ثانية.']);
        }

        $admin = Admin::where('username', trim($request->input('username')))->first();

        if (! $admin || ! Hash::check($request->input('password'), $admin->password) || ! $admin->is_active) {
            RateLimiter::hit($key, 300);

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'اسم المستخدم أو كلمة المرور غير صحيحة.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
