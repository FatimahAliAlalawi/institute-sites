<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** الصفحات المخصصة + القوائم (الرأس والتذييل) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('cover')->nullable();
            $table->boolean('show_header')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location', 20)->default('header'); // header | footer
            $table->string('label');
            $table->string('type', 10)->default('route'); // route | page | url
            $table->string('target', 500);
            $table->boolean('new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // القوائم الحالية كما هي — قابلة للتعديل من لوحة التحكم ← القوائم
        $now = now();
        $rows = [];
        foreach ([
            'header' => [['home', 'الرئيسية'], ['about', 'من نحن'], ['courses.index', 'الدورات'], ['programs.index', 'البرامج'], ['news.index', 'الأخبار'], ['contact', 'تواصل معنا']],
            'footer' => [['home', 'الرئيسية'], ['about', 'من نحن'], ['courses.index', 'الدورات'], ['programs.index', 'البرامج'], ['news.index', 'الأخبار'], ['contact', 'تواصل معنا'], ['register', 'التسجيل في البرامج'], ['certificates.verify', 'التحقق من شهادة']],
        ] as $location => $items) {
            foreach ($items as $i => [$route, $label]) {
                $rows[] = ['location' => $location, 'label' => $label, 'type' => 'route', 'target' => $route, 'new_tab' => false, 'is_active' => true, 'sort' => $i + 1, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('menu_items')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('pages');
    }
};
