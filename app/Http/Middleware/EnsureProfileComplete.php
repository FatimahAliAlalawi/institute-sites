<?php

namespace App\Http\Middleware;

use App\Support\ProfileCompletion;
use Closure;
use Illuminate\Http\Request;

/** يحوّل الطالب الناقصة بياناته الإلزامية إلى صفحة "أكمل بياناتك" */
class EnsureProfileComplete
{
    protected const ALLOWED = ['logout', 'profile.complete', 'profile.complete.save'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! in_array($request->route()?->getName(), self::ALLOWED, true) && ProfileCompletion::missing($user)->isNotEmpty()) {
            if ($request->isMethod('GET')) {
                redirect()->setIntendedUrl($request->fullUrl());
            }

            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
