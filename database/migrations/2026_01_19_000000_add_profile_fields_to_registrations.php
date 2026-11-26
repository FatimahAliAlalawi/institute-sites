<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * بيانات المتدرب الكاملة: الهوية وتاريخ الميلاد ووقت إكمال البيانات.
 * نموذج التسجيل الأولي يصير مختصرًا: الدورة والجوال والهوية فقط، والباقي يكمله المتدرب بعد الدفع من رابطه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $t) {
            if (! Schema::hasColumn('registrations', 'national_id')) {
                $t->string('national_id', 20)->nullable();
            }
            if (! Schema::hasColumn('registrations', 'birth_date')) {
                $t->date('birth_date')->nullable();
            }
            if (! Schema::hasColumn('registrations', 'profile_done_at')) {
                $t->timestamp('profile_done_at')->nullable();
            }
        });

        if (! Schema::hasTable('form_fields')) {
            return;
        }
        $now = now();
        if (! DB::table('form_fields')->where('form', 'registration')->where('key', 'national_id')->exists()) {
            $phoneSort = (int) DB::table('form_fields')->where('form', 'registration')->where('key', 'phone')->value('sort');
            DB::table('form_fields')->insert([
                'form' => 'registration', 'key' => 'national_id', 'label' => 'رقم الهوية / الإقامة', 'type' => 'national_id',
                'placeholder' => '1xxxxxxxxx', 'help' => null, 'options' => null, 'required' => true, 'is_active' => true,
                'is_system' => true, 'width' => 'half', 'sort' => $phoneSort, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        // النموذج الأولي: الدورة + الجوال + الهوية فقط (يمكن إظهار أي حقل من «النماذج»)
        DB::table('form_fields')->where('form', 'registration')->whereNotIn('key', ['program_id', 'phone', 'national_id'])
            ->update(['is_active' => false, 'required' => false, 'updated_at' => $now]);
        DB::table('form_fields')->where('form', 'registration')->whereIn('key', ['phone', 'national_id'])
            ->update(['is_active' => true, 'required' => true, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('registrations', fn (Blueprint $t) => $t->dropColumn(['national_id', 'birth_date', 'profile_done_at']));
    }
};
