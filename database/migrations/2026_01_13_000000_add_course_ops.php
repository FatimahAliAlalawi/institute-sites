<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * جدول الدورة (الأيام، الوقت، المدرب، رابط المجموعة) + تشغيل الدورة:
 * خطوات «آلية التسجيل» بالمسؤولين لكل دورة، وخطوات كل متدرب.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('schedule_days', 120)->nullable()->after('duration');
            $table->string('schedule_time', 80)->nullable()->after('schedule_days');
            $table->string('trainer_name', 120)->nullable()->after('schedule_time');
            $table->string('group_link', 255)->nullable()->after('contact_phone');
            $table->json('ops')->nullable()->after('approval_note');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->json('steps')->nullable()->after('coupon_code');
        });

        // المسؤولون الافتراضيون عن خطوات آلية التسجيل (من آلية معهد البصائر). قابلة للتعديل من صفحة «تشغيل الدورة».
        if (Schema::hasTable('settings') && ! DB::table('settings')->where('key', 'ops_assignees')->exists()) {
            $site = (string) DB::table('settings')->where('key', 'site_name')->value('value');
            if (str_contains($site, 'البصائر')) {
                DB::table('settings')->insert([
                    'key' => 'ops_assignees',
                    'value' => json_encode([
                        'register' => 'ابتسام، فاطمة، باسل',
                        'invoice' => 'باسل، ابتسام',
                        'invoice_sent' => 'ابتسام، باسل',
                        'welcome' => 'تورة، ابتسام، باسل',
                        'prep_sheet' => 'أ. محمد، أ. تورة',
                        'room' => 'أ. محمد، أ. تورة',
                        'reminder_1d' => 'ابتسام، باسل',
                        'reminder_am' => 'ابتسام، باسل',
                        'tvtc' => 'فاطمة الحماد',
                    ], JSON_UNESCAPED_UNICODE),
                ]);
                \Illuminate\Support\Facades\Cache::forget('site_settings');
            }
        }
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['schedule_days', 'schedule_time', 'trainer_name', 'group_link', 'ops']);
        });
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('steps');
        });
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'ops_assignees')->delete();
        }
    }
};
