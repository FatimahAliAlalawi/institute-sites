<section class="hero">
    <div class="container">
        <div>
            @if (setting('hero_badge'))<span class="badge">{{ setting('hero_badge') }}</span>@endif
            <h1>{{ setting('hero_title', setting('site_name')) }}</h1>
            <p>{{ setting('hero_subtitle') }}</p>
            <div class="actions">
                @if (setting('hero_cta_text'))
                    <a href="{{ url(setting('hero_cta_link', '/register')) }}" class="btn btn-light">{{ setting('hero_cta_text') }}</a>
                @endif
                @if (setting('hero_cta2_text'))
                    <a href="{{ url(setting('hero_cta2_link', '/programs')) }}" class="btn btn-ghost">{{ setting('hero_cta2_text') }}</a>
                @endif
            </div>
            @if ($journey = lines(setting('journey')))
                <div class="journey">
                    @foreach ($journey as $step)<span>{{ $step }}</span>@endforeach
                </div>
            @endif
        </div>
        @if (setting('hero_image'))
            <div class="hero-card photo"><img src="{{ media_url(setting('hero_image')) }}" alt="{{ setting('site_name') }}"></div>
        @else
            <div class="hero-card"><img src="{{ media_url(setting('logo', 'assets/img/logo.png')) }}" alt="{{ setting('site_name') }}"></div>
        @endif
    </div>
</section>
