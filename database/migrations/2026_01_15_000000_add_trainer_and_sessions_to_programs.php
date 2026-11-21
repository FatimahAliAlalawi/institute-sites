<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حساب المدرب (يرى تحضير دوراته فقط) وتقويم أيام الدورة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('trainer_admin_id')->nullable()->after('trainer_name')->constrained('admins')->nullOnDelete();
            $table->json('sessions')->nullable()->after('trainer_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_admin_id');
            $table->dropColumn('sessions');
        });
    }
};
