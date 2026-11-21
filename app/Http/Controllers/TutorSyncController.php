<?php

namespace App\Http\Controllers;

use App\Support\TutorSync;

/** رابط المهمة المجدولة: cPanel ← Cron Jobs أو cron-job.org يستدعيه كل بضع دقائق */
class TutorSyncController extends Controller
{
    public function __invoke(string $token)
    {
        abort_unless(TutorSync::enabled() && hash_equals(TutorSync::token(), $token), 404);

        $report = TutorSync::run('cron');
        if ($report === null) {
            return response('busy: sync already running', 200)->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $lines = [$report['status']];
        foreach ($report['counts'] as $label => $count) {
            $lines[] = $label.': '.$count;
        }

        return response(implode("\n", $lines), $report['status'] === 'ok' ? 200 : 500)->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
