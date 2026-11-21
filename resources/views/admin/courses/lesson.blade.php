@extends('layouts.admin')

@section('title', $lesson->exists ? 'تعديل درس' : 'درس جديد — '.$topic->title)

@section('actions')
    <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn">← رجوع للمنهج</a>
@endsection

@section('content')
    <form method="post" enctype="multipart/form-data" action="{{ $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store', $topic) }}">
        @csrf
        @if ($lesson->exists) @method('PUT') @endif
        <div class="card">
            <div class="form-grid">
                <div class="field full">
                    <label for="title">عنوان الدرس *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $lesson->title) }}" required>
                </div>
                <div class="field">
                    <label for="video_url">رابط الفيديو</label>
                    <input type="url" id="video_url" name="video_url" value="{{ old('video_url', $lesson->video_url) }}" dir="ltr" placeholder="https://www.youtube.com/watch?v=...">
                    <div class="hint">يدعم: يوتيوب (يفضّل "غير مدرج")، فيميو، Google Drive، أو رابط ملف mp4 مباشر.</div>
                </div>
                <div class="field">
                    <label for="duration">مدة الدرس</label>
                    <input type="text" id="duration" name="duration" value="{{ old('duration', $lesson->duration) }}" placeholder="مثال: 12:30">
                </div>
                <div class="field full">
                    <label for="content">محتوى الدرس (نص)</label>
                    <textarea id="content" name="content" class="tall" style="min-height:280px">{{ old('content', $lesson->content) }}</textarea>
                    <div class="hint">شرح، ملاحظات، روابط... يظهر تحت الفيديو.</div>
                </div>
                <div class="field">
                    <label for="attachment">مرفق للتحميل (اختياري)</label>
                    <input type="file" id="attachment" name="attachment">
                    <div class="hint">PDF، Word، Excel، PowerPoint، ZIP... حتى 20 ميجابايت. يحمّله المشتركون فقط.</div>
                    @if ($lesson->attachment)
                        <label class="checks" style="font-weight:500;margin-top:8px"><i class="fa-solid fa-paperclip"></i> {{ $lesson->attachment_name }} — <input type="checkbox" name="remove_attachment" value="1"> حذف</label>
                    @endif
                </div>
                <div class="field">
                    @if ($lesson->exists)
                        <label for="topic_id">الوحدة</label>
                        <select id="topic_id" name="topic_id">
                            @foreach ($course->topics as $t)
                                <option value="{{ $t->id }}" @selected((int) old('topic_id', $lesson->topic_id) === $t->id)>{{ $t->title }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label for="sort" style="margin-top:12px">الترتيب داخل الوحدة</label>
                    <input type="number" id="sort" name="sort" value="{{ old('sort', $lesson->sort ?? 0) }}">
                </div>
                <div class="field full checks">
                    <label><input type="checkbox" name="is_preview" value="1" @checked(old('is_preview', $lesson->is_preview))> معاينة مجانية (يشاهده الزوار بدون اشتراك)</label>
                </div>
            </div>
        </div>
        <div class="form-foot sticky-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ الدرس</button>
            <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn">إلغاء</a>
        </div>
    </form>
@endsection
