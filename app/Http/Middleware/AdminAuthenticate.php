<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\Program;
use App\Models\Registration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $admin = ($id = $request->session()->get('admin_id')) ? Admin::with('role')->find($id) : null;

        if (! $admin || ! $admin->is_active) {
            $request->session()->forget('admin_id');
            if ($request->isMethod('get')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('admin.login')->withErrors($admin ? ['username' => 'تم إيقاف هذا الحساب. تواصل مع المدير العام.'] : []);
        }

        View::share('currentAdmin', $admin);
        View::share('badges', [
            'registrations' => $admin->allows('registrations') ? Registration::where(fn ($q) => $q->where('status', 'new')->orWhere(fn ($d) => $d->dueFollowUp()))->count() : 0,
            'messages' => $admin->allows('messages') ? Message::where('is_read', false)->count() : 0,
            'enrollments' => $admin->allows('enrollments') ? Enrollment::where('status', 'pending')->count() : 0,
            'approvals' => $admin->allows('approvals') ? Program::where('plan_status', 'pending')->orWhere('ad_status', 'pending')->count() : 0,
        ]);

        // الصلاحيات: كل قسم مرتبط بصلاحية (المدير العام يرى كل شيء)
        if (! $admin->canOpenRoute($request->route()?->getName())) {
            return response()->view('admin.forbidden', [], 403);
        }

        return $next($request);
    }
}
