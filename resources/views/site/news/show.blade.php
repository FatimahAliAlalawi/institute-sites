@extends('layouts.site')

@section('title', $post->title)
@section('description', $post->excerpt)
@if ($post->image)
    @section('og_image', media_url($post->image))
@endif

@section('content')
    <section class="page-head">
        <div class="container">
            <div class="crumbs"><a href="{{ route('home') }}">الرئيسية</a> / <a href="{{ route('news.index') }}">الأخبار</a></div>
            <h1>{{ $post->title }}</h1>
            <p><i class="fa-regular fa-calendar"></i> {{ $post->date?->translatedFormat('j F Y') }}</p>
        </div>
    </section>

    <section class="section">
        <article class="container article">
            @if ($post->image)
                <img class="cover" src="{{ media_url($post->image) }}" alt="{{ $post->title }}">
            @endif
            <div class="prose">{{ $post->body }}</div>
        </article>
    </section>

    @if ($latest->isNotEmpty())
        <section class="section alt">
            <div class="container">
                <div class="section-head"><h2>أخبار أخرى</h2><div class="divider"></div></div>
                <div class="grid-3">
                    @foreach ($latest as $post)
                        @include('partials.post-card')
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
