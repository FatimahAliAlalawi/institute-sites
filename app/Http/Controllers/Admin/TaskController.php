<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Registration;
use App\Support\TaskBoard;
use Illuminate\Http\Request;

/** «مهامي» لكل موظف، و«متابعة الفريق» للإدارة */
class TaskController extends Controller
{
    public function mine(Request $request)
    {
        $admin = Admin::with('role')->find($request->session()->get('admin_id'));
        $tasks = TaskBoard::forAdmin($admin);
        $dueFollowUps = $admin->allows('registrations') ? Registration::dueFollowUp()->count() : null;

        return view('admin.tasks.mine', compact('admin', 'tasks', 'dueFollowUps'));
    }

    public function team()
    {
        $team = TaskBoard::team();
        $programs = TaskBoard::activePrograms();

        return view('admin.tasks.team', compact('team', 'programs'));
    }
}
