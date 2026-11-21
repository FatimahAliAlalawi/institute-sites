@extends('layouts.admin')

@section('title', $faq->exists ? 'تعديل سؤال' : 'سؤال جديد')

@section('content')
    <form method="post" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}">
        @csrf
        @if ($faq->exists) @method('PUT') @endif
        <div class="card">
            <div class="form-grid">
                <div class="field full">
                    <label for="question">السؤال *</label>
                    <input type="text" id="question" name="question" value="{{ old('question', $faq->question) }}" required>
                </div>
                <div class="field full">
                    <label for="answer">الإجابة *</label>
                    <textarea id="answer" name="answer" required>{{ old('answer', $faq->answer) }}</textarea>
                </div>
                <div class="field">
                    <label for="sort">الترتيب</label>
                    <input type="number" id="sort" name="sort" value="{{ old('sort', $faq->sort ?? 0) }}">
                </div>
                <div class="field checks" style="align-self:center">
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active))> ظاهر في الموقع</label>
                </div>
            </div>
        </div>
        <div class="form-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn">رجوع</a>
        </div>
    </form>
@endsection
