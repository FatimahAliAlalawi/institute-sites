<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// التذكير التلقائي لمتدربي الدورات الحضورية (اختياري عبر cron: php artisan trainees:remind كل ساعة)
Artisan::command('trainees:remind', function () {
    $this->info('أُرسلت '.\App\Support\AutoReminders::run().' رسالة تذكير.');
})->purpose('إرسال تذكيرات الدورات الحضورية بالإيميل');
