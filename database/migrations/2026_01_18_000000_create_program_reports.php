<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ملفات المدرب لكل دورة: الخطة الأسبوعية، التقرير الأسبوعي، تقرير الاختبار القبلي والبعدي */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_reports')) {
            return;
        }
        Schema::create('program_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 20);
            $t->date('week')->nullable();
            $t->string('path')->nullable();
            $t->string('original_name')->nullable();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('admin_id')->nullable();
            $t->string('admin_name')->nullable();
            $t->timestamps();
            $t->index(['program_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_reports');
    }
};
