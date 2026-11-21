<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\TutorImporter;
use App\Support\TutorSync;
use Illuminate\Http\Request;

/** استيراد الدورات والطلاب من موقع ووردبريس + Tutor LMS */
class ImportController extends Controller
{
    public function show()
    {
        return view('admin.import.tutor', ['input' => ['db_host' => 'localhost', 'db_port' => 3306, 'prefix' => 'wp_', 'with_students' => true, 'with_progress' => true, 'with_reviews' => true]]);
    }

    /** حفظ بيانات المزامنة التلقائية (بعد التأكد من الاتصال) */
    public function saveSync(Request $request)
    {
        abort_unless(TutorSync::available(), 404);
        $data = $request->validate([
            'db_host' => 'required|string|max:255',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|string|max:255',
            'db_username' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'prefix' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'uploads_path' => 'nullable|string|max:500',
            'site_url' => 'nullable|url|max:255',
            'interval' => 'required|integer|min:1|max:1440',
        ], [], [
            'db_host' => 'خادم قاعدة البيانات', 'db_port' => 'المنفذ', 'db_database' => 'اسم قاعدة البيانات',
            'db_username' => 'مستخدم قاعدة البيانات', 'prefix' => 'بادئة الجداول', 'interval' => 'مدة المزامنة',
        ]);

        $config = [
            'host' => $data['db_host'], 'port' => (int) $data['db_port'], 'database' => $data['db_database'], 'username' => $data['db_username'],
            // كلمة المرور لا تُعرض: إن تُركت فارغة تبقى المحفوظة
            'password' => filled($data['db_password'] ?? null) ? $data['db_password'] : (TutorSync::config()['password'] ?? ''),
            'prefix' => $data['prefix'], 'uploads_path' => $data['uploads_path'] ?? null, 'site_url' => $data['site_url'] ?? null,
        ];
        $enabled = $request->boolean('enabled');

        try {
            TutorSync::importer($config)->preview();
        } catch (\Throwable $e) {
            return back()->withInput($request->except('db_password'))
                ->withErrors('تعذّر الاتصال بقاعدة بيانات الموقع القديم — لم تُحفظ الإعدادات: '.$e->getMessage());
        }

        TutorSync::saveConfig($config);
        Setting::put(['tutor_sync_enabled' => $enabled ? '1' : '0', 'tutor_sync_interval' => (string) $data['interval']]);
        TutorSync::token();

        return redirect()->route('admin.import.tutor')->with('status', $enabled
            ? 'تم الاتصال وحُفظت الإعدادات — المزامنة التلقائية تعمل الآن. اضغط «مزامنة الآن» لأول مرة.'
            : 'تم الاتصال وحُفظت الإعدادات (المزامنة التلقائية متوقفة).');
    }

    public function runSync()
    {
        abort_unless(TutorSync::available() && TutorSync::config(), 404);
        $report = TutorSync::run('manual');

        if ($report === null) {
            return back()->withErrors('توجد مزامنة جارية الآن، انتظر دقيقة ثم أعد المحاولة.');
        }

        if ($report['status'] !== 'ok') {
            return back()->withErrors('فشلت المزامنة: '.($report['warnings'][0] ?? ''));
        }

        return back()->with('status', 'تمت المزامنة — '
            .(collect($report['counts'])->map(fn ($n, $label) => $label.': '.$n)->implode('، ') ?: 'لا جديد في الموقع القديم.'));
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'db_host' => 'required|string|max:255',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|string|max:255',
            'db_username' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'prefix' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'uploads_path' => 'nullable|string|max:500',
            'site_url' => 'nullable|url|max:255',
            'action' => 'required|in:preview,import',
        ], [], [
            'db_host' => 'خادم قاعدة البيانات', 'db_port' => 'المنفذ', 'db_database' => 'اسم قاعدة البيانات',
            'db_username' => 'مستخدم قاعدة البيانات', 'prefix' => 'بادئة الجداول', 'uploads_path' => 'مسار مجلد uploads', 'site_url' => 'رابط الموقع القديم',
        ]);
        $input = $data + [
            'with_students' => $request->boolean('with_students'),
            'with_progress' => $request->boolean('with_progress'),
            'with_reviews' => $request->boolean('with_reviews'),
        ];

        $preview = $report = $error = null;
        try {
            $importer = new TutorImporter(
                ['host' => $data['db_host'], 'port' => $data['db_port'], 'database' => $data['db_database'], 'username' => $data['db_username'], 'password' => $data['db_password'] ?? ''],
                ['prefix' => $data['prefix'], 'uploads_path' => $data['uploads_path'] ?? null, 'site_url' => $data['site_url'] ?? null]
            );
            if ($data['action'] === 'preview') {
                $preview = $importer->preview();
            } else {
                $report = $importer->run($input['with_students'], $input['with_progress'], $input['with_reviews']);
            }
        } catch (\Throwable $e) {
            report($e);
            $error = $e instanceof \PDOException || str_contains($e->getMessage(), 'SQLSTATE')
                ? 'تعذّر الاتصال بقاعدة بيانات الموقع القديم: '.$e->getMessage()
                : $e->getMessage();
        }

        return view('admin.import.tutor', compact('input', 'preview', 'report', 'error'));
    }
}
