<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use App\Support\SchemaUpdater;
use Closure;
use Illuminate\Http\Request;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (! Installer::installed()) {
            return redirect('/install');
        }

        // بعد رفع نسخة محدّثة: تطبيق تحديثات قاعدة البيانات تلقائيًا (مرة واحدة)
        SchemaUpdater::ensureUpToDate();

        return $next($request);
    }
}
