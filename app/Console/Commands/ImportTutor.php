<?php

namespace App\Console\Commands;

use App\Support\TutorImporter;
use Illuminate\Console\Command;

/** نفس استيراد لوحة التحكم لكن من سطر الأوامر (لمن لديه SSH أو للمواقع الكبيرة) */
class ImportTutor extends Command
{
    protected $signature = 'lms:import-tutor
        {--host=localhost} {--port=3306} {--database=} {--username=} {--password=}
        {--prefix=wp_} {--uploads= : مسار مجلد wp-content/uploads} {--site-url= : رابط الموقع القديم}
        {--driver=mysql} {--preview : عرض الأعداد فقط} {--no-students} {--no-progress} {--no-reviews}';

    protected $description = 'استيراد الدورات والطلاب من ووردبريس + Tutor LMS';

    public function handle(): int
    {
        if (! $this->option('database')) {
            $this->error('حدّد --database');

            return self::FAILURE;
        }

        $importer = new TutorImporter(
            ['driver' => $this->option('driver'), 'host' => $this->option('host'), 'port' => $this->option('port'),
                'database' => $this->option('database'), 'username' => $this->option('username'), 'password' => $this->option('password')],
            ['prefix' => $this->option('prefix'), 'uploads_path' => $this->option('uploads'), 'site_url' => $this->option('site-url')]
        );

        if ($this->option('preview')) {
            foreach ($importer->preview() as $label => $count) {
                $this->line($label.': '.$count);
            }

            return self::SUCCESS;
        }

        $report = $importer->run(! $this->option('no-students'), ! $this->option('no-progress'), ! $this->option('no-reviews'));
        foreach ($report['counts'] as $label => $count) {
            $this->info($label.': '.$count);
        }
        foreach ($report['warnings'] as $warning) {
            $this->warn('- '.$warning);
        }

        return self::SUCCESS;
    }
}
