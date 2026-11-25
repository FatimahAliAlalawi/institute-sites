<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** الاختبار القبلي والبعدي لكل دورة + متابعة تنفيذ الخطة (كل خطوة تتحقق أولًا حتى يمكن إعادتها بأمان) */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('program_questions')) {
            Schema::create('program_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained()->cascadeOnDelete();
                $table->text('question');
                $table->json('options');
                $table->unsignedTinyInteger('correct')->default(0);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('program_test_results')) {
            Schema::create('program_test_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained()->cascadeOnDelete();
                $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
                $table->string('kind', 10); // pre | post
                $table->unsignedTinyInteger('score'); // نسبة مئوية
                $table->unsignedInteger('earned')->nullable();
                $table->unsignedInteger('total')->nullable();
                $table->json('answers')->nullable(); // null = درجة أُدخلت يدويًا (اختبار ورقي)
                $table->string('recorded_by', 190)->nullable();
                $table->timestamps();
                $table->unique(['registration_id', 'kind']);
                $table->index(['program_id', 'kind']);
            });
        }

        foreach ([
            'pre_test_open' => fn (Blueprint $t) => $t->boolean('pre_test_open')->default(false),
            'post_test_open' => fn (Blueprint $t) => $t->boolean('post_test_open')->default(false),
            'plan_baseline_date' => fn (Blueprint $t) => $t->date('plan_baseline_date')->nullable(),
            'plan_exec' => fn (Blueprint $t) => $t->string('plan_exec', 20)->nullable(),
            'plan_exec_note' => fn (Blueprint $t) => $t->string('plan_exec_note', 500)->nullable(),
        ] as $column => $add) {
            if (! Schema::hasColumn('programs', $column)) {
                Schema::table('programs', $add);
            }
        }

        if (! Schema::hasColumn('registrations', 'test_token')) {
            Schema::table('registrations', fn (Blueprint $t) => $t->string('test_token', 64)->nullable()->unique());
        }

        // الدورات المعتمدة حاليًا: موعدها الحالي هو الموعد المخطط
        DB::table('programs')->where('plan_status', 'approved')->whereNull('plan_baseline_date')
            ->update(['plan_baseline_date' => DB::raw('start_date')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('program_test_results');
        Schema::dropIfExists('program_questions');
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['test_token']);
            $table->dropColumn('test_token');
        });
        Schema::table('programs', fn (Blueprint $t) => $t->dropColumn(['pre_test_open', 'post_test_open', 'plan_baseline_date', 'plan_exec', 'plan_exec_note']));
    }
};
