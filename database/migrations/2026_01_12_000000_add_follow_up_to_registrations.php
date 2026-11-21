<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قائمة المتابعة داخل طلبات التسجيل (بدل ملف Google Sheet):
 * مصدر المهتم، موعد المتابعة، رقم الفاتورة، تأكيد الحضور، كود الخصم المرسل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('source', 40)->nullable()->after('status');
            $table->date('follow_up_at')->nullable()->after('source');
            $table->string('invoice_no', 60)->nullable()->after('follow_up_at');
            $table->string('attendance', 20)->nullable()->after('invoice_no');
            $table->string('coupon_code', 60)->nullable()->after('attendance');
            $table->index('follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['follow_up_at']);
            $table->dropColumn(['source', 'follow_up_at', 'invoice_no', 'attendance', 'coupon_code']);
        });
    }
};
