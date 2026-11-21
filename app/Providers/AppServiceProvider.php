<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // دوال مساعدة عامة: setting() و media_url()
        require_once app_path('helpers.php');
    }

    public function boot(): void
    {
        // حماية النماذج من الإغراق: عدّاد مستقل لكل نموذج ولكل عنوان IP
        // (حدود مرنة لأن موظفي جهة واحدة قد يسجّلون من نفس الشبكة)
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(30)->by($request->ip().'|'.$request->route()?->getName()));
        RateLimiter::for('logins', fn (Request $request) => Limit::perMinute(30)->by($request->ip().'|'.$request->route()?->getName()));

        // توافق مع إصدارات MySQL القديمة في بعض الاستضافات (حد طول الفهارس)
        Schema::defaultStringLength(191);

        // أسماء الأشهر بالعربي في التواريخ
        Carbon::setLocale(config('app.locale', 'ar'));

        // بريد المعهد من الإعدادات (لوحة التحكم ← الإعدادات ← البريد الإلكتروني)
        try {
            \App\Support\MailSettings::apply();
        } catch (\Throwable $e) {
            report($e);
        }

        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // المزامنة التلقائية مع الموقع القديم: بعد إرسال الصفحة للزائر، كل بضع دقائق
        if (! $this->app->runningInConsole()) {
            $this->app->terminating(function () {
                try {
                    \App\Support\TutorSync::autoRun();
                } catch (\Throwable $e) {
                    report($e);
                }
                try {
                    \App\Support\RegistrationImporter::autoRun();
                } catch (\Throwable $e) {
                    report($e);
                }
                try {
                    \App\Support\AutoReminders::autoRun();
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        }
    }
}
