@extends('layouts.site')

@section('title', $category?->name ?? 'الدورات التدريبية')

@section('content')
    @include('partials.page-head', [
        'title' => $category?->name ?? 'الدورات التدريبية',
        'crumb' => 'الدورات',
        'subtitle' => 'تعلّم في أي وقت ومن أي مكان، وتابع تقدمك واحصل على شهادة معتمدة',
    ])

    <section class="section">
        <div class="container">
            <form class="course-filters" method="get" action="{{ route('courses.index') }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="ابحث عن دورة...">
                <select name="category" onchange="this.form.submit()">
                    <option value="">كل التصنيفات</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->slug }}" @selected($category?->id === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select name="level" onchange="this.form.submit()">
                    <option value="">كل المستويات</option>
                    @foreach (\App\Models\Course::LEVELS as $key => $label)
                        <option value="{{ $key }}" @selected($level === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="price" onchange="this.form.submit()">
                    <option value="">المجانية والمدفوعة</option>
                    <option value="free" @selected($price === 'free')>المجانية فقط</option>
                    <option value="paid" @selected($price === 'paid')>المدفوعة فقط</option>
                </select>
            </form>

            @if ($courses->isEmpty())
                <div class="empty">لا توجد دورات مطابقة حاليًا.</div>
            @else
                <div class="grid-3">
                    @foreach ($courses as $course)
                        @include('partials.course-card')
                    @endforeach
                </div>
                {{ $courses->links() }}
            @endif
        </div>
    </section>
@endsection
