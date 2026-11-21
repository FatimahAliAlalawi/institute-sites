<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** الدورات المسجلة بنظام "درس يوميًا" + أيام الدراسة الخاصة بكل متدرب */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('daily_release')->default(false)->after('certificate_enabled');
            $table->unsignedTinyInteger('lessons_per_day')->default(1)->after('daily_release');
        });

        Schema::table('users', function (Blueprint $table) {
            // أرقام الأيام مفصولة بفواصل (0 = الأحد ... 6 = السبت)، فارغ = الإعداد العام
            $table->string('study_days', 20)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn(['daily_release', 'lessons_per_day']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('study_days'));
    }
};
