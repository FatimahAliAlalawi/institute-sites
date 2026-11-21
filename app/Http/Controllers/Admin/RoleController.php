<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الأدوار: مجموعة صلاحيات تُعطى للمستخدمين */
class RoleController extends Controller
{
    public function create()
    {
        return view('admin.users.role', ['role' => new Role(['permissions' => []])]);
    }

    public function store(Request $request)
    {
        $role = Role::create($this->validated($request));

        return redirect()->route('admin.users.index')->with('status', 'تم إنشاء الدور «'.$role->name.'».');
    }

    public function edit(Role $role)
    {
        return view('admin.users.role', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $role->update($this->validated($request));

        return redirect()->route('admin.users.index')->with('status', 'تم حفظ الدور «'.$role->name.'».');
    }

    public function destroy(Role $role)
    {
        if ($role->admins()->exists()) {
            return back()->withErrors(['role' => 'لا يمكن حذف دور مرتبط بمستخدمين — غيّر دورهم أولًا.']);
        }
        $role->delete();

        return back()->with('status', 'تم حذف الدور.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array|min:1',
            'permissions.*' => [Rule::in(Permissions::all())],
        ], ['permissions.required' => 'اختر صلاحية واحدة على الأقل.'], ['name' => 'اسم الدور']);
        $data['permissions'] = array_values(array_unique($data['permissions']));

        return $data;
    }
}
