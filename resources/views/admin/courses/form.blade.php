@extends('layouts.admin')

@section('title', $course->exists ? 'تعديل: '.$course->title : 'دورة جديدة')

@section('actions')
    @if ($course->exists)
        <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn btn-primary"><i class="fa-solid fa-list-check"></i> المنهج والدروس</a>
        <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn"><i class="fa-regular fa-eye"></i> عرض</a>
    @endif
@endsection

@section('content')
    <form method="post" enctype="multipart/form-data" action="{{ $course->exists ? route('admin.courses.update', $course) : route('admin.courses.store') }}">
        @csrf
        @if ($course->exists) @method('PUT') @endif

        <div class="card">
            <h2>المعلومات الأساسية</h2>
            <div class="form-grid">
                <div class="field full">
                    <label for="title">عنوان الدورة *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required>
                </div>
                <div class="field full">
                    <label for="summary">وصف مختصر</label>
                    <input type="text" id="summary" name="summary" value="{{ old('summary', $course->summary) }}" maxlength="500">
                </div>
                <div class="field">
                    <label for="category_id">التصنيف</label>
                    <select id="category_id" name="category_id">
                        <option value="">— بدون —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) old('category_id', $course->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <div class="hint"><a href="{{ route('admin.course-categories.index') }}" target="_blank">إدارة التصنيفات</a></div>
                </div>
                <div class="field">
                    <label for="instructor_id">المدرب</label>
                    <select id="instructor_id" name="instructor_id">
                        <option value="">— بدون —</option>
                        @foreach ($instructors as $ins)
                            <option value="{{ $ins->id }}" @selected((string) old('instructor_id', $course->instructor_id) === (string) $ins->id)>{{ $ins->name }}</option>
                        @endforeach
                    </select>
                    <div class="hint"><a href="{{ route('admin.instructors.create') }}" target="_blank">إضافة مدرب</a></div>
                </div>
                <div class="field full">
                    <label for="description">وصف الدورة</label>
                    <textarea id="description" name="description" class="tall">{{ old('description', $course->description) }}</textarea>
                </div>
                <div class="field">
                    <label for="what_you_learn">ماذا سيتعلم الطالب؟</label>
                    <textarea id="what_you_learn" name="what_you_learn">{{ old('what_you_learn', $course->what_you_learn) }}</textarea>
                    <div class="hint">كل نقطة في سطر.</div>
                </div>
                <div class="field">
                    <label for="requirements">المتطلبات</label>
                    <textarea id="requirements" name="requirements">{{ old('requirements', $course->requirements) }}</textarea>
                    <div class="hint">كل متطلب في سطر.</div>
                </div>
                <div class="field">
                    <label for="target_audience">الفئة المستهدفة</label>
                    <input type="text" id="target_audience" name="target_audience" value="{{ old('target_audience', $course->target_audience) }}">
                </div>
                <div class="field">
                    <label for="level">المستوى</label>
                    <select id="level" name="level">
                        @foreach (\App\Models\Course::LEVELS as $key => $label)
                            <option value="{{ $key }}" @selected(old('level', $course->level) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="duration_text">مدة الدورة</label>
                    <input type="text" id="duration_text" name="duration_text" value="{{ old('duration_text', $course->duration_text) }}" placeholder="مثال: 6 ساعات">
                </div>
                <div class="field">
                    <label for="intro_video">رابط الفيديو التعريفي</label>
                    <input type="url" id="intro_video" name="intro_video" value="{{ old('intro_video', $course->intro_video) }}" dir="ltr" placeholder="https://youtube.com/...">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>السعر</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="price">السعر (ريال)</label>
                    <input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', $course->price) }}">
                    <div class="hint">اتركه فارغًا أو 0 = دورة مجانية.</div>
                </div>
                <div class="field">
                    <label for="sale_price">سعر بعد الخصم (اختياري)</label>
                    <input type="number" step="0.01" min="0" id="sale_price" name="sale_price" value="{{ old('sale_price', $course->sale_price) }}">
                </div>
                <div class="field">
                    <label for="sale_starts_at">بداية العرض (اختياري)</label>
                    <input type="datetime-local" id="sale_starts_at" name="sale_starts_at" value="{{ old('sale_starts_at', $course->sale_starts_at?->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="field">
                    <label for="sale_ends_at">نهاية العرض (اختياري)</label>
                    <input type="datetime-local" id="sale_ends_at" name="sale_ends_at" value="{{ old('sale_ends_at', $course->sale_ends_at?->format('Y-m-d\TH:i')) }}">
                    <div class="hint">يعود السعر الأصلي تلقائيًا بعد انتهاء العرض.</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><i class="fa-solid fa-calendar-day"></i> نظام الدورة المسجلة (درس يوميًا)</h2>
            <div class="form-grid">
                <div class="field full checks">
                    <label><input type="checkbox" name="daily_release" value="1" @checked(old('daily_release', $course->daily_release))> دورة مسجلة: تُفتح الدروس للمتدرب تدريجيًا في أيام الدراسة فقط، مع تحضير يومي</label>
                </div>
                <div class="field">
                    <label for="lessons_per_day">عدد الدروس الجديدة المتاحة يوميًا</label>
                    <input type="number" id="lessons_per_day" name="lessons_per_day" min="1" max="20" value="{{ old('lessons_per_day', $course->lessons_per_day ?? 1) }}">
                </div>
                <div class="field">
                    <label>أيام الدراسة</label>
                    <div class="hint" style="margin-top:8px">الافتراضي: <b>{{ \App\Support\DailyRelease::label(\App\Support\DailyRelease::globalDays()) }}</b> — يُغيَّر من <a href="{{ route('admin.settings.edit') }}#study">الإعدادات</a>، ويمكن تخصيص أيام لكل متدرب من صفحته.</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>الصورة والنشر</h2>
            <div class="form-grid">
                @include('admin.partials.image-field', ['name' => 'thumbnail', 'label' => 'صورة الدورة', 'value' => $course->thumbnail, 'hint' => 'يفضّل 1280×800 — JPG/PNG/WEBP حتى 4 ميجابايت'])
                <div class="field">
                    <label for="slug">الرابط المختصر</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $course->slug) }}">
                    <div class="hint">يُولَّد تلقائيًا إن تُرك فارغًا.</div>
                    <label for="sort" style="margin-top:12px">الترتيب</label>
                    <input type="number" id="sort" name="sort" value="{{ old('sort', $course->sort ?? 0) }}">
                </div>
                <div class="field full checks">
                    <label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $course->is_published))> منشورة (تظهر للطلاب)</label>
                    <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $course->is_featured))> تظهر في الصفحة الرئيسية</label>
                    <label><input type="checkbox" name="certificate_enabled" value="1" @checked(old('certificate_enabled', $course->certificate_enabled))> إصدار شهادة عند الإتمام</label>
                </div>
            </div>
        </div>

        <div class="form-foot sticky-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{ $course->exists ? 'حفظ' : 'حفظ والانتقال لإضافة الدروس' }}</button>
            <a href="{{ route('admin.courses.index') }}" class="btn">رجوع</a>
        </div>
    </form>
@endsection
