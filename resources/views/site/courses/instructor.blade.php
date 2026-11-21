@extends('layouts.site')

@section('title', $instructor->name)

@section('content')
    @include('partials.page-head', ['title' => $instructor->name, 'crumb' => 'المدربون', 'subtitle' => $instructor->title])

    <section class="section">
        <div class="container">
            <div class="panel instructor-box">
                @if ($instructor->photo)
                    <img class="avatar lg" src="{{ media_url($instructor->photo) }}" alt="">
                @else
                    <span class="avatar lg">{{ mb_substr($instructor->name, 0, 1) }}</span>
                @endif
                <div>
                    <h2 style="margin:0">{{ $instructor->name }}</h2>
                    <div class="muted">{{ $instructor->title }}</div>
                    <p class="prose" style="margin-top:10px">{{ $instructor->bio }}</p>
                </div>
            </div>

            <h2 style="color:var(--primary);margin-top:30px">دورات المدرب ({{ $courses->count() }})</h2>
            @if ($courses->isEmpty())
                <div class="empty">لا توجد دورات منشورة لهذا المدرب.</div>
            @else
                <div class="grid-3">
                    @foreach ($courses as $course)
                        @include('partials.course-card')
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
