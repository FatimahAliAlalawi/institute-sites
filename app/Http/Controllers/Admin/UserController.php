<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** مستخدمو لوحة التحكم (الموظفون) */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => Admin::with('role')->orderByDesc('is_super')->orderBy('name')->get(),
            'roles' => Role::withCount('admins')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new Admin(['is_active' => true]), 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        Admin::create($data);

        return redirect()->route('admin.users.index')->with('status', 'تم إنشاء حساب «'.$data['name'].'».');
    }

    public function edit(Request $request, Admin $user)
    {
        $this->guardSuper($request, $user);

        return view('admin.users.form', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, Admin $user)
    {
        $this->guardSuper($request, $user);
        $data = $this->validated($request, $user);
        $me = $this->me($request);

        if ($user->id === $me->id) {
            // لا يمكنك إيقاف حسابك أو سحب صلاحية المدير العام من نفسك
            $data['is_active'] = true;
            $data['is_super'] = $user->is_super;
        }
        if ($user->is_super && empty($data['is_super']) && Admin::where('is_super', true)->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['is_super' => 'يجب أن يبقى مدير عام واحد على الأقل.']);
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'تم حفظ حساب «'.$user->name.'».');
    }

    public function destroy(Request $request, Admin $user)
    {
        $this->guardSuper($request, $user);
        if ($user->id === $this->me($request)->id) {
            return back()->withErrors(['user' => 'لا يمكنك حذف حسابك.']);
        }
        if ($user->is_super && Admin::where('is_super', true)->count() <= 1) {
            return back()->withErrors(['user' => 'لا يمكن حذف آخر مدير عام.']);
        }
        $user->delete();

        return back()->with('status', 'تم حذف الحساب.');
    }

    protected function validated(Request $request, ?Admin $user): array
    {
        $isSuperEditor = $this->me($request)->is_super;
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[A-Za-z0-9_.@-]+$/', Rule::unique('admins', 'username')->ignore($user?->id)],
            'email' => 'nullable|email|max:190',
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role_id' => 'nullable|exists:roles,id',
        ], ['username.regex' => 'اسم المستخدم بالأحرف الإنجليزية والأرقام فقط (بدون مسافات).'], [
            'name' => 'الاسم', 'username' => 'اسم المستخدم', 'password' => 'كلمة المرور', 'role_id' => 'الدور',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        // منح "مدير عام" متاح للمدير العام فقط
        $data['is_super'] = $isSuperEditor ? $request->boolean('is_super') : false;
        if (! $data['is_super'] && empty($data['role_id'])) {
            abort(back()->withInput()->withErrors(['role_id' => 'اختر دورًا للمستخدم أو اجعله مديرًا عامًا.']));
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    protected function me(Request $request): Admin
    {
        return Admin::findOrFail($request->session()->get('admin_id'));
    }

    /** حسابات المدير العام لا يعدّلها إلا مدير عام */
    protected function guardSuper(Request $request, Admin $user): void
    {
        abort_if($user->is_super && ! $this->me($request)->is_super, 403, 'لا يمكن تعديل حساب المدير العام.');
    }
}
