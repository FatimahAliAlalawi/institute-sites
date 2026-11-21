@extends('layouts.site')

@section('title', 'من نحن')

@section('content')
    @include('partials.page-head', ['title' => setting('about_title', 'من نحن'), 'crumb' => 'من نحن', 'subtitle' => setting('site_tagline')])

    <section class="section">
        <div class="container grid-2">
            <div>
                <h2 style="color:var(--primary)">{{ setting('site_name') }}</h2>
                <p class="prose">{{ setting('about_text') }}</p>
            </div>
            <div @class(['about-img', 'photo' => setting('about_image')])>
                <img src="{{ media_url(setting('about_image', setting('logo', 'assets/img/logo.png'))) }}" alt="{{ setting('site_name') }}">
            </div>
        </div>
    </section>

    <section class="section alt">
        <div class="container grid-3">
            @if (setting('vision'))
                <div class="vm">
                    <div class="icon"><i class="fa-regular fa-eye"></i></div>
                    <h3>رؤيتنا</h3>
                    <p class="prose muted">{{ setting('vision') }}</p>
                </div>
            @endif
            @if (setting('mission'))
                <div class="vm">
                    <div class="icon"><i class="fa-solid fa-bullseye"></i></div>
                    <h3>رسالتنا</h3>
                    <p class="prose muted">{{ setting('mission') }}</p>
                </div>
            @endif
            @if ($values = lines(setting('values')))
                <div class="vm">
                    <div class="icon"><i class="fa-regular fa-gem"></i></div>
                    <h3>قيمنا</h3>
                    <ul class="check-list">
                        @foreach ($values as $value)<li>{{ $value }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    @if ($partners->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head"><h2>شركاؤنا</h2><div class="divider"></div></div>
                @include('partials.partners')
            </div>
        </section>
    @endif

    @if ($faqs->isNotEmpty())
        <section class="section alt">
            <div class="container">
                <div class="section-head"><h2>الأسئلة الشائعة</h2><div class="divider"></div></div>
                @include('partials.faqs')
            </div>
        </section>
    @endif

    @include('partials.cta')
@endsection
