@extends('layouts.admin')

@section('title', 'اختبار: '.$quiz->title)

@section('actions')
    <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn">← رجوع للمنهج</a>
@endsection

@section('content')
    <form class="card" method="post" action="{{ route('admin.quizzes.update', $quiz) }}">
        @csrf @method('PUT')
        <h2>إعدادات الاختبار</h2>
        <div class="form-grid">
            <div class="field"><label>العنوان *</label><input type="text" name="title" value="{{ old('title', $quiz->title) }}" required></div>
            <div class="field">
                <label>الوحدة</label>
                <select name="topic_id">
                    @foreach ($topics as $t)
                        <option value="{{ $t->id }}" @selected((int) old('topic_id', $quiz->topic_id) === $t->id)>{{ $t->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field full"><label>تعليمات للطالب</label><textarea name="description" style="min-height:70px">{{ old('description', $quiz->description) }}</textarea></div>
            <div class="field"><label>درجة النجاح (%)</label><input type="number" name="pass_mark" min="0" max="100" value="{{ old('pass_mark', $quiz->pass_mark) }}" required></div>
            <div class="field"><label>الوقت بالدقائق</label><input type="number" name="time_limit" min="1" value="{{ old('time_limit', $quiz->time_limit) }}" placeholder="فارغ = بدون وقت"></div>
            <div class="field"><label>عدد المحاولات المسموح</label><input type="number" name="max_attempts" min="1" value="{{ old('max_attempts', $quiz->max_attempts) }}" placeholder="فارغ = غير محدود"></div>
            <div class="field"><label>الترتيب داخل الوحدة</label><input type="number" name="sort" value="{{ old('sort', $quiz->sort) }}"></div>
            <div class="field full checks"><label><input type="checkbox" name="show_answers" value="1" @checked(old('show_answers', $quiz->show_answers))> عرض الإجابات الصحيحة للطالب بعد التسليم</label></div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ الإعدادات</button></div>
    </form>

    <div class="card" id="questions">
        <h2>الأسئلة ({{ $quiz->questions->count() }})</h2>
        @forelse ($quiz->questions as $q)
            <div style="border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:10px">
                <div class="top" style="margin:0">
                    <b>{{ $loop->iteration }}. {{ $q->question }}</b>
                    <div class="actions">
                        <span class="badge b-off">{{ \App\Models\QuizQuestion::TYPES[$q->type] ?? $q->type }} · {{ $q->points }} درجة</span>
                        <a href="{{ route('admin.questions.edit', $q) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                        @include('admin.partials.delete-button', ['action' => route('admin.questions.destroy', $q), 'label' => ''])
                    </div>
                </div>
                <div class="sub" style="margin-top:6px">
                    @foreach ($q->options as $o)
                        <span style="margin-inline-end:12px;{{ ! empty($o['correct']) ? 'color:var(--success);font-weight:700' : '' }}">{{ ! empty($o['correct']) ? '✔' : '○' }} {{ $o['text'] }}</span>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty">لا توجد أسئلة بعد.</div>
        @endforelse
    </div>

    <form class="card" method="post" action="{{ route('admin.questions.store', $quiz) }}" data-type="{{ old('type', 'single') }}">
        @csrf
        <h2><i class="fa-solid fa-plus"></i> إضافة سؤال</h2>
        @include('admin.courses.question-fields', ['question' => $question])
        <div class="form-foot"><button class="btn btn-primary" type="submit">إضافة السؤال</button></div>
    </form>
@endsection
