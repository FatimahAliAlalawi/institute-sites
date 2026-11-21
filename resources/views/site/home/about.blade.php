<section class="section">
    <div class="container grid-2">
        <div>
            <span class="muted">{{ \App\Support\HomeSections::text('about', 'eyebrow', 'من نحن') }}</span>
            <h2 style="color:var(--primary);font-size:32px">{{ setting('about_title', 'عن المعهد') }}</h2>
            <p class="prose">{{ \Illuminate\Support\Str::limit((string) setting('about_text'), 420) }}</p>
            @if ($values = lines(setting('values')))
                <ul class="check-list" style="margin-bottom:24px">
                    @foreach (array_slice($values, 0, 4) as $value)<li>{{ $value }}</li>@endforeach
                </ul>
            @endif
            <a href="{{ route('about') }}" class="btn btn-outline">اعرف أكثر عن المعهد</a>
        </div>
        <div @class(['about-img', 'photo' => setting('about_image')])>
            <img src="{{ media_url(setting('about_image', setting('logo', 'assets/img/logo.png'))) }}" alt="{{ setting('site_name') }}">
        </div>
    </div>
</section>
