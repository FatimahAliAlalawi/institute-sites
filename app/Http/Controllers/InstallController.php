<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * معالج التثبيت من المتصفح: يكفي رفع الملفات بمدير الملفات ثم فتح /install
 * (بدون أي أوامر SSH أو Composer على الاستضافة).
 */
class InstallController extends Controller
{
    public function show(Request $request)
    {
        return view('install', [
            'checks' => $this->requirements(),
            'detectedUrl' => $this->detectUrl($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'app_url' => 'required|url|max:255',
            'db_host' => 'required|string|max:255',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|string|max:255',
            'db_username' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'admin_name' => 'required|string|max:100',
            'admin_username' => 'required|string|min:3|max:100',
            'admin_password' => 'required|string|min:8|confirmed',
            'with_demo' => 'nullable|boolean',
        ], [], [
            'app_url' => 'رابط الموقع',
            'db_host' => 'خادم قاعدة البيانات',
            'db_port' => 'المنفذ',
            'db_database' => 'اسم قاعدة البيانات',
            'db_username' => 'مستخدم قاعدة البيانات',
            'admin_name' => 'اسم المدير',
            'admin_username' => 'اسم مستخدم المدير',
            'admin_password' => 'كلمة مرور المدير',
        ]);
        $safeInput = $request->except(['db_password', 'admin_password', 'admin_password_confirmation']);

        // 1) اختبار الاتصال بقاعدة البيانات
        try {
            $pdo = new \PDO(
                'mysql:host='.$data['db_host'].';port='.$data['db_port'].';dbname='.$data['db_database'].';charset=utf8mb4',
                $data['db_username'],
                (string) ($data['db_password'] ?? ''),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 8]
            );
            $pdo->query('SELECT 1');
        } catch (\Throwable $e) {
            return back()->withInput($safeInput)->withErrors(['db_host' => 'تعذّر الاتصال بقاعدة البيانات: '.$e->getMessage()]);
        }

        // 2) تطبيق الإعدادات على هذا الطلب فورًا
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => (string) ($data['db_password'] ?? ''),
        ]);
        DB::purge('mysql');

        // 3) إنشاء الجداول + المحتوى الافتراضي + حساب المدير
        try {
            Artisan::call('migrate', ['--force' => true]);
            if ($request->boolean('with_demo', true)) {
                Artisan::call('db:seed', ['--force' => true]);
            }
            Admin::updateOrCreate(
                ['username' => trim($data['admin_username'])],
                ['name' => $data['admin_name'], 'password' => $data['admin_password'], 'is_super' => true, 'is_active' => true]
            );
        } catch (\Throwable $e) {
            return back()->withInput($safeInput)->withErrors(['db_host' => 'فشل إنشاء الجداول: '.$e->getMessage()]);
        }

        // 4) حفظ الإعدادات في ملف .env
        $env = [
            'APP_NAME' => 'Albasaer',
            'APP_ENV' => 'production',
            'APP_KEY' => (string) (config('app.key') ?: 'base64:'.base64_encode(random_bytes(32))),
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($data['app_url'], '/'),
            'APP_TIMEZONE' => 'Asia/Riyadh',
            'APP_LOCALE' => 'ar',
            'APP_FALLBACK_LOCALE' => 'en',
            'LOG_CHANNEL' => 'daily',
            'LOG_LEVEL' => 'warning',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => (string) ($data['db_password'] ?? ''),
            'SESSION_DRIVER' => 'file',
            'SESSION_LIFETIME' => '720',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'FILESYSTEM_DISK' => 'local',
            'MAIL_MAILER' => 'log',
        ];
        $content = '';
        foreach ($env as $key => $value) {
            $content .= $key.'='.$this->envValue($value)."\n";
        }

        $written = @file_put_contents(base_path('.env'), $content) !== false;
        @file_put_contents(Installer::lockFile(), date('c'));
        \App\Support\SchemaUpdater::markCurrent();

        if (! $written) {
            return view('install-done', ['envContent' => $content]);
        }

        return redirect('/admin/login')->with('status', 'تم التثبيت بنجاح ✅ سجّل دخولك بحساب المدير الذي أنشأته.');
    }

    protected function envValue(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_\-:\/.@+=]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    protected function detectUrl(Request $request): string
    {
        $root = $request->getSchemeAndHttpHost().$request->getBaseUrl();

        return rtrim(Str::replaceLast('/public', '', $root), '/');
    }

    protected function requirements(): array
    {
        return [
            'PHP 8.2 أو أحدث' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'امتداد PDO MySQL' => extension_loaded('pdo_mysql'),
            'امتداد mbstring' => extension_loaded('mbstring'),
            'امتداد OpenSSL' => extension_loaded('openssl'),
            'امتداد fileinfo (لرفع الصور)' => extension_loaded('fileinfo'),
            'مجلد storage قابل للكتابة' => is_writable(storage_path()) && is_writable(storage_path('framework')),
            'مجلد bootstrap/cache قابل للكتابة' => is_writable(base_path('bootstrap/cache')),
            'مجلد public/uploads قابل للكتابة' => is_writable(public_path('uploads')),
            'ملف .env قابل للكتابة' => is_writable(base_path('.env')) || (! file_exists(base_path('.env')) && is_writable(base_path())),
        ];
    }
}
