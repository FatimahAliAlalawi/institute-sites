<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** المحاضرات المباشرة (Zoom) داخل منهج الدورة + سجل حضورها */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('course_topics')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration')->default(60);
            $table->string('zoom_meeting_id', 50)->nullable();
            $table->string('join_url', 1000)->nullable();
            $table->text('start_url')->nullable();
            $table->string('password', 50)->nullable();
            $table->string('recording_url', 1000)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('live_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->unsignedInteger('join_count')->default(1);
            $table->timestamps();
            $table->unique(['live_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_attendances');
        Schema::dropIfExists('live_sessions');
    }
};
