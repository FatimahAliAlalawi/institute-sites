<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

/** يقفل معالج التثبيت بعد اكتماله (لا يمكن إعادة التثبيت فوق بيانات موجودة من المتصفح) */
class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (Installer::installed()) {
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
