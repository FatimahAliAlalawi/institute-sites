<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** بيانات الطالب الأساسية + جعلها مطلوبة في نموذج إنشاء الحساب */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id', 20)->nullable()->unique()->after('phone');
            $table->date('birth_date')->nullable()->after('national_id');
            $table->string('gender', 10)->nullable()->after('birth_date');
            $table->string('qualification', 100)->nullable()->after('gender');
        });

        // ترتيب نموذج إنشاء الحساب: الاسم الرباعي، الهوية، الميلاد، الجنس، المؤهل، الجوال، البريد، كلمة المرور
        $now = now();
        $fields = [
            'name' => ['label' => 'الاسم الرباعي', 'type' => 'text', 'required' => true, 'width' => 'full', 'help' => 'كما في الهوية — يُكتب في الشهادة كما هو.', 'placeholder' => 'الاسم الأول واسم الأب واسم الجد واسم العائلة'],
            'national_id' => ['label' => 'رقم الهوية الوطنية / الإقامة', 'type' => 'national_id', 'required' => true, 'width' => 'half', 'placeholder' => '10 أرقام'],
            'birth_date' => ['label' => 'تاريخ الميلاد', 'type' => 'date', 'required' => true, 'width' => 'half'],
            'gender' => ['label' => 'الجنس', 'type' => 'radio', 'required' => true, 'width' => 'half', 'options' => "ذكر\nأنثى"],
            'qualification' => ['label' => 'المؤهل الدراسي', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => "ثانوي أو أقل\nدبلوم\nبكالوريوس\nماجستير\nدكتوراه"],
            'phone' => ['label' => 'رقم الجوال', 'type' => 'tel', 'required' => true, 'width' => 'half', 'placeholder' => '05xxxxxxxx'],
            'email' => ['label' => 'البريد الإلكتروني', 'type' => 'email', 'required' => true, 'width' => 'half'],
            'password' => ['label' => 'كلمة المرور', 'type' => 'password', 'required' => true, 'width' => 'full'],
        ];
        $sort = 1;
        foreach ($fields as $key => $values) {
            $exists = DB::table('form_fields')->where('form', 'signup')->where('key', $key)->exists();
            $row = $values + ['options' => null, 'help' => null, 'placeholder' => null, 'is_active' => true, 'is_system' => true, 'sort' => $sort++, 'updated_at' => $now];
            if ($exists) {
                DB::table('form_fields')->where('form', 'signup')->where('key', $key)->update($row);
            } else {
                DB::table('form_fields')->insert($row + ['form' => 'signup', 'key' => $key, 'created_at' => $now]);
            }
        }
        // الحقول الإضافية التي أضافها المدير (إن وُجدت) تأتي بعدها
        DB::table('form_fields')->where('form', 'signup')->where('is_system', false)->increment('sort', 20);
    }

    public function down(): void
    {
        DB::table('form_fields')->where('form', 'signup')->whereIn('key', ['national_id', 'birth_date', 'gender', 'qualification'])->delete();
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['national_id']);
            $table->dropColumn(['national_id', 'birth_date', 'gender', 'qualification']);
        });
    }
};
