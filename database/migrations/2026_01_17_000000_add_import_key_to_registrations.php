<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** نص الدورة كما جاء من النموذج: يعرف السحب طلبه حتى لو نُقل لدورة أخرى أو تغيّر اسمها، فلا يكرره */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('registrations', 'import_key')) {
            Schema::table('registrations', fn (Blueprint $t) => $t->string('import_key', 190)->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('registrations', fn (Blueprint $t) => $t->dropColumn('import_key'));
    }
};
