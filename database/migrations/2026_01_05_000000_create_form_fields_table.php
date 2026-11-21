<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** منشئ النماذج: حقول نماذج التسجيل وإنشاء الحساب والتواصل قابلة للتعديل من لوحة التحكم */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('form', 30);
            $table->string('key', 60);
            $table->string('label');
            $table->string('type', 20)->default('text');
            $table->text('options')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('help', 500)->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->string('width', 10)->default('half');
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->unique(['form', 'key']);
        });

        // إجابات الحقول الإضافية
        foreach (['registrations', 'users', 'messages'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->json('extra')->nullable());
        }

        // الحقول الافتراضية (نفس النماذج السابقة) — يمكن تعديلها كلها من لوحة التحكم
        $now = now();
        $rows = [];
        $add = function (string $form, string $key, string $label, string $type, array $extra = []) use (&$rows, $now) {
            $rows[] = array_merge([
                'form' => $form, 'key' => $key, 'label' => $label, 'type' => $type, 'options' => null, 'placeholder' => null,
                'help' => null, 'required' => false, 'is_active' => true, 'is_system' => true, 'width' => 'half',
                'sort' => count(array_filter($rows, fn ($r) => $r['form'] === $form)) + 1, 'created_at' => $now, 'updated_at' => $now,
            ], $extra);
        };

        $add('registration', 'program_id', 'البرنامج', 'program', ['width' => 'full']);
        $add('registration', 'name', 'الاسم الكامل', 'text', ['required' => true]);
        $add('registration', 'phone', 'رقم الجوال', 'tel', ['required' => true, 'placeholder' => '05xxxxxxxx']);
        $add('registration', 'email', 'البريد الإلكتروني', 'email');
        $add('registration', 'city', 'المدينة', 'text');
        $add('registration', 'qualification', 'المؤهل الدراسي', 'select', ['options' => "ثانوي أو أقل\nدبلوم\nبكالوريوس\nدراسات عليا"]);
        $add('registration', 'employment_status', 'الحالة الوظيفية', 'select', ['options' => "باحث عن عمل\nموظف قطاع خاص\nموظف قطاع حكومي\nموظف قطاع غير ربحي\nطالب"]);
        $add('registration', 'gender', 'الجنس', 'radio', ['options' => "ذكر\nأنثى"]);
        $add('registration', 'notes', 'ملاحظات أو استفسار', 'textarea', ['width' => 'full']);

        $add('signup', 'name', 'الاسم الكامل', 'text', ['required' => true, 'width' => 'full', 'help' => 'يُكتب في الشهادة كما هو.']);
        $add('signup', 'email', 'البريد الإلكتروني', 'email', ['required' => true, 'width' => 'full']);
        $add('signup', 'phone', 'رقم الجوال', 'tel', ['required' => true, 'width' => 'full', 'placeholder' => '05xxxxxxxx']);
        $add('signup', 'password', 'كلمة المرور', 'password', ['required' => true, 'width' => 'full']);

        $add('contact', 'name', 'الاسم', 'text', ['required' => true, 'width' => 'full']);
        $add('contact', 'phone', 'الجوال', 'tel');
        $add('contact', 'email', 'البريد الإلكتروني', 'email');
        $add('contact', 'subject', 'الموضوع', 'text', ['width' => 'full']);
        $add('contact', 'body', 'الرسالة', 'textarea', ['required' => true, 'width' => 'full']);

        DB::table('form_fields')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
        foreach (['registrations', 'users', 'messages'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('extra'));
        }
    }
};
