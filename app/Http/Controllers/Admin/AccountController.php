<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function edit()
    {
        return view('admin.account.edit');
    }

    public function update(Request $request)
    {
        $admin = Admin::findOrFail($request->session()->get('admin_id'));

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:100', Rule::unique('admins', 'username')->ignore($admin->id)],
            'current_password' => 'required|string',
            'password' => 'nullable|string|min:8|confirmed',
        ], [], ['password' => 'كلمة المرور الجديدة']);

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $admin->name = $data['name'];
        $admin->username = trim($data['username']);
        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }
        $admin->save();

        return back()->with('status', 'تم حفظ بيانات الحساب.');
    }
}
