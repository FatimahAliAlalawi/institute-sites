@extends('layouts.admin')

@section('title', 'تعديل سؤال')

@section('actions')
    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn">← رجوع للاختبار</a>
@endsection

@section('content')
    <form class="card" method="post" action="{{ route('admin.questions.update', $question) }}" data-type="{{ old('type', $question->type) }}">
        @csrf @method('PUT')
        @include('admin.courses.question-fields', ['question' => $question])
        <div class="field" style="max-width:200px;margin-top:12px"><label>الترتيب</label><input type="number" name="sort" value="{{ old('sort', $question->sort) }}"></div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ السؤال</button></div>
    </form>
@endsection
