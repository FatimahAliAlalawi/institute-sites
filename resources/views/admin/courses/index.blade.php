@extends('layouts.admin')

@section('title', 'الدورات')

@section('actions')
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> دورة جديدة</a>
@endsection

@section('content')
    <div class="card">
        <form class="filters" method="get">
            <div class="field"><label>بحث</label><input type="search" name="q" value="{{ request('q') }}" placeholder="اسم الدورة"></div>
            <div class="field">
                <label>التصنيف</label>
                <select name="category">
                    <option value="">الكل</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) request('category') === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> تصفية</button>
        </form>

        @if ($courses->isEmpty())
            <div class="empty">لا توجد دورات بعد. <a href="{{ route('admin.courses.create') }}">أنشئ أول دورة</a></div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th></th><th>الدورة</th><th>المحتوى</th><th>الطلاب</th><th>السعر</th><th>الحالة</th><th></th></tr>
                    @foreach ($courses as $course)
                        <tr>
                            <td>@if ($course->thumbnail)<img class="thumb" src="{{ media_url($course->thumbnail) }}" alt="">@else<div class="thumb"></div>@endif</td>
                            <td>
                                <a href="{{ route('admin.courses.edit', $course) }}"><b>{{ $course->title }}</b></a>
                                @if ($course->is_featured)<span class="badge b-new">مميزة</span>@endif
                                <div class="sub">{{ $course->category?->name ?? 'بدون تصنيف' }} · {{ $course->instructor?->name ?? 'بدون مدرب' }}</div>
                            </td>
                            <td class="sub">{{ $course->lessons_count }} درس · {{ $course->quizzes_count }} اختبار</td>
                            <td><a href="{{ route('admin.enrollments.index', ['course' => $course->id]) }}">{{ $course->students_count }}</a></td>
                            <td>{{ $course->price_label }}</td>
                            <td><span class="badge {{ $course->is_published ? 'b-on' : 'b-off' }}">{{ $course->is_published ? 'منشورة' : 'مسودة' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-list-check"></i> المنهج</a>
                                    <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                    <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn btn-sm"><i class="fa-regular fa-eye"></i></a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.courses.destroy', $course), 'label' => '', 'confirm' => 'سيتم حذف الدورة وكل دروسها واختباراتها واشتراكاتها. متأكد؟'])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $courses->links() }}
        @endif
    </div>
@endsection
