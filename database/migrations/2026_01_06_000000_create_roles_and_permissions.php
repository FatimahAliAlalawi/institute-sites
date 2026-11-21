<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** مستخدمو لوحة التحكم وصلاحياتهم (أدوار) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->string('email')->nullable()->after('username');
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
            $table->boolean('is_super')->default(false)->after('role_id');
            $table->boolean('is_active')->default(true)->after('is_super');
        });

        // المدراء الحاليون (قبل هذه الميزة) يصبحون مدراء عامين بكل الصلاحيات
        DB::table('admins')->update(['is_super' => true]);

        // أدوار جاهزة — قابلة للتعديل من لوحة التحكم
        $now = now();
        DB::table('roles')->insert([
            ['name' => 'مسؤول التسجيل والتواصل', 'description' => 'طلبات التسجيل في البرامج ورسائل التواصل', 'permissions' => json_encode(['registrations', 'messages']), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'مسؤول المحتوى', 'description' => 'البرامج والأخبار والشركاء والأسئلة الشائعة', 'permissions' => json_encode(['programs', 'posts', 'partners', 'faqs']), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'مسؤول المنصة التعليمية', 'description' => 'الدورات والطلاب والاشتراكات والتحضير والنتائج', 'permissions' => json_encode(['courses', 'students', 'enrollments', 'attendance', 'lms_reports']), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'المحاسب', 'description' => 'مراجعة المدفوعات وتفعيل الاشتراكات', 'permissions' => json_encode(['enrollments']), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['email', 'is_super', 'is_active']);
        });
        Schema::dropIfExists('roles');
    }
};
