<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** تحضير الدورات الحضورية (البرامج): حالة كل متدرب في كل يوم */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20); // present | late | absent | excused
            $table->string('note', 255)->nullable();
            $table->string('recorded_by', 190)->nullable();
            $table->timestamps();
            $table->unique(['registration_id', 'date']);
            $table->index(['program_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_attendances');
    }
};
