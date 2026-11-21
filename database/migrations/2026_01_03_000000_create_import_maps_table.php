<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** دعم الاستيراد من النظام السابق (ووردبريس + Tutor LMS) */
return new class extends Migration
{
    public function up(): void
    {
        // ربط كل عنصر مستورد برقمه القديم — يمنع التكرار عند إعادة الاستيراد
        Schema::create('import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('legacy_id', 64);
            $table->unsignedBigInteger('local_id');
            $table->timestamps();
            $table->unique(['type', 'legacy_id']);
        });

        // كلمة مرور ووردبريس القديمة: يدخل بها الطالب أول مرة ثم تُحوَّل تلقائيًا
        Schema::table('users', function (Blueprint $table) {
            $table->string('legacy_password')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_maps');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('legacy_password');
        });
    }
};
