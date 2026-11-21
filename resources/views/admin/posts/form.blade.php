@extends('layouts.admin')

@section('title', $post->exists ? 'تعديل خبر' : 'خبر جديد')

@section('content')
    <form method="post" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
        @csrf
        @if ($post->exists) @method('PUT') @endif

        <div class="card">
            <div class="form-grid">
                <div class="field full">
                    <label for="title">العنوان *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required>
                </div>
                <div class="field full">
                    <label for="excerpt">ملخص قصير</label>
                    <input type="text" id="excerpt" name="excerpt" value="{{ old('excerpt', $post->excerpt) }}" maxlength="500">
                </div>
                <div class="field full">
                    <label for="body">نص الخبر</label>
                    <textarea id="body" name="body" class="tall" style="min-height:320px">{{ old('body', $post->body) }}</textarea>
                    <div class="hint">اكتب النص كما تريده أن يظهر؛ الأسطر والفقرات تُحفظ كما هي.</div>
                </div>
                @include('admin.partials.image-field', ['name' => 'image', 'label' => 'صورة الخبر', 'value' => $post->image])
                <div class="field">
                    <label for="published_at">تاريخ النشر</label>
                    <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                    <div class="hint">تاريخ مستقبلي = يُنشر تلقائيًا في ذلك الوقت.</div>
                </div>
                <div class="field">
                    <label for="slug">الرابط المختصر</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $post->slug) }}">
                    <div class="hint">يُولَّد تلقائيًا من العنوان إن تُرك فارغًا.</div>
                </div>
                <div class="field checks" style="align-self:center">
                    <label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->is_published))> منشور</label>
                </div>
            </div>
        </div>

        <div class="form-foot sticky-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ</button>
            <a href="{{ route('admin.posts.index') }}" class="btn">رجوع</a>
        </div>
    </form>
@endsection
