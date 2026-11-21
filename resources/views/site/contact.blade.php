@extends('layouts.site')

@section('title', \App\Support\FormBuilder::text('contact', 'title'))

@section('content')
    @include('partials.page-head', ['title' => \App\Support\FormBuilder::text('contact', 'title'), 'subtitle' => \App\Support\FormBuilder::text('contact', 'intro')])

    <section class="section">
        <div class="container grid-2" style="align-items:start">
            <div>
                <h2 style="color:var(--primary)">بيانات التواصل</h2>
                <p class="muted">تواصل معنا بأي وسيلة تناسبك، أو أرسل رسالتك عبر النموذج.</p>
                <div class="contact-items">
                    @if (setting('phone'))
                        <a class="contact-item" href="tel:{{ setting('phone') }}"><span class="ic"><i class="fa-solid fa-phone"></i></span><span><small>الهاتف</small><b>{{ setting('phone') }}</b></span></a>
                    @endif
                    @if (setting('whatsapp'))
                        <a class="contact-item" href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp')) }}" target="_blank" rel="noopener"><span class="ic"><i class="fa-brands fa-whatsapp"></i></span><span><small>واتساب</small><b>{{ setting('whatsapp') }}</b></span></a>
                    @endif
                    @if (setting('email'))
                        <a class="contact-item" href="mailto:{{ setting('email') }}"><span class="ic"><i class="fa-regular fa-envelope"></i></span><span><small>البريد الإلكتروني</small><b>{{ setting('email') }}</b></span></a>
                    @endif
                    @if (setting('address'))
                        <a class="contact-item" @if (setting('map_url')) href="{{ setting('map_url') }}" target="_blank" rel="noopener" @endif><span class="ic"><i class="fa-solid fa-location-dot"></i></span><span><small>العنوان</small><b>{{ setting('address') }}</b></span></a>
                    @endif
                    @if (setting('working_hours'))
                        <div class="contact-item"><span class="ic"><i class="fa-regular fa-clock"></i></span><span><small>أوقات العمل</small><b>{{ setting('working_hours') }}</b></span></div>
                    @endif
                </div>
            </div>

            <div class="form-card">
                @if (session('success'))
                    <div class="alert alert-success">{{ \App\Support\FormBuilder::text('contact', 'success') }}</div>
                @endif
                <form class="form" method="post" action="{{ route('contact.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="fields-grid">
                        @foreach ($fields as $field)
                            @include('partials.form-field', ['field' => $field])
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">{{ \App\Support\FormBuilder::text('contact', 'submit') }}</button>
                </form>
            </div>
        </div>

        @if (setting('map_embed'))
            <div class="container">
                <iframe class="map" src="{{ setting('map_embed') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="الموقع على الخريطة"></iframe>
            </div>
        @endif
    </section>
@endsection
