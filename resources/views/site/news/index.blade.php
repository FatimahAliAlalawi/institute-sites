@extends('layouts.site')

@section('title', 'الأخبار')

@section('content')
    @include('partials.page-head', ['title' => 'الأخبار والفعاليات', 'crumb' => 'الأخبار', 'subtitle' => 'آخر أخبار المعهد وبرامجه'])

    <section class="section">
        <div class="container">
            @if ($posts->isEmpty())
                <div class="empty">لا توجد أخبار منشورة بعد.</div>
            @else
                <div class="grid-3">
                    @foreach ($posts as $post)
                        @include('partials.post-card')
                    @endforeach
                </div>
                {{ $posts->links() }}
            @endif
        </div>
    </section>
@endsection
