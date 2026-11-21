<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الخطة التدريبية للأفراد: بيانات الإعلان المعتمد (رقم الاعتماد، الخصم، تاريخ الانتهاء، رقم التواصل)
 * واعتماد الإدارة للخطة وللإعلان.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('accreditation_no', 100)->nullable()->after('certificate');
            $table->decimal('sale_price', 10, 2)->nullable()->after('price');
            $table->date('offer_ends_at')->nullable()->after('start_date');
            $table->string('contact_phone', 30)->nullable()->after('offer_ends_at');
            $table->string('plan_status', 20)->default('none')->after('contact_phone');
            $table->string('plan_approved_by', 190)->nullable()->after('plan_status');
            $table->timestamp('plan_approved_at')->nullable()->after('plan_approved_by');
            $table->string('ad_status', 20)->default('none')->after('plan_approved_at');
            $table->string('ad_approved_by', 190)->nullable()->after('ad_status');
            $table->timestamp('ad_approved_at')->nullable()->after('ad_approved_by');
            $table->string('approval_note', 1000)->nullable()->after('ad_approved_at');
        });

        // رابط "خطة الأفراد" في القائمة العلوية بعد "البرامج" (يمكن حذفه أو نقله من لوحة التحكم ← القوائم)
        if (Schema::hasTable('menu_items') && ! DB::table('menu_items')->where('target', 'programs.plan')->exists()) {
            $after = DB::table('menu_items')->where('location', 'header')->where('target', 'programs.index')->value('sort');
            $sort = $after !== null ? $after + 1 : 99;
            DB::table('menu_items')->where('location', 'header')->where('sort', '>=', $sort)->increment('sort');
            DB::table('menu_items')->insert([
                'location' => 'header', 'label' => 'خطة الأفراد', 'type' => 'route', 'target' => 'programs.plan',
                'new_tab' => false, 'is_active' => true, 'sort' => $sort, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'accreditation_no', 'sale_price', 'offer_ends_at', 'contact_phone', 'plan_status', 'plan_approved_by',
                'plan_approved_at', 'ad_status', 'ad_approved_by', 'ad_approved_at', 'approval_note',
            ]);
        });

        if (Schema::hasTable('menu_items')) {
            DB::table('menu_items')->where('target', 'programs.plan')->delete();
        }
    }
};
