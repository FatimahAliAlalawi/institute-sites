@extends('layouts.admin')

@section('title', $instructor->exists ? 'تعديل مدرب' : 'مدرب جديد')

@section('content')
    <form method="post" enctype="multipart/form-data" action="{{ $instructor->exists ? route('admin.instructors.update', $instructor) : route('admin.instructors.store') }}">
        @csrf
        @if ($instructor->exists) @method('PUT') @endif
        <div class="card">
            <div class="form-grid">
                <div class="field"><label>الاسم *</label><input type="text" name="name" value="{{ old('name', $instructor->name) }}" required></div>
                <div class="field"><label>المسمى / التخصص</label><input type="text" name="title" value="{{ old('title', $instructor->title) }}" placeholder="مثال: مدرب معتمد في الموارد البشرية"></div>
                <div class="field full"><label>نبذة</label><textarea name="bio">{{ old('bio', $instructor->bio) }}</textarea></div>
                @include('admin.partials.image-field', ['name' => 'photo', 'label' => 'الصورة', 'value' => $instructor->photo, 'hint' => 'صورة مربعة — حتى 2 ميجابايت'])
                <div class="field"><label>الترتيب</label><input type="number" name="sort" value="{{ old('sort', $instructor->sort ?? 0) }}"></div>
            </div>
        </div>
        <div class="form-foot">
            <button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> حفظ</button>
            <a href="{{ route('admin.instructors.index') }}" class="btn">رجوع</a>
        </div>
    </form>
@endsection
