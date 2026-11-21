@extends('layouts.site')

@section('title', $page->title)
@section('description', $page->meta_description ?: $page->subtitle)
@if ($page->cover)
    @section('og_image', media_url($page->cover))
@endif

@section('content')
    @if ($page->show_header)
        @include('partials.page-head', ['title' => $page->title, 'subtitle' => $page->subtitle])
    @endif

    <section class="section" @unless ($page->show_header) style="padding-top:40px" @endunless>
        <div class="container" style="max-width:900px">
            @unless ($page->show_header)<h1 style="color:var(--primary)">{{ $page->title }}</h1>@endunless
            @if ($page->cover)<img src="{{ media_url($page->cover) }}" alt="{{ $page->title }}" style="border-radius:20px;margin-bottom:26px;width:100%">@endif
            <div class="rich-content">{!! $page->content !!}</div>
        </div>
    </section>
@endsection
