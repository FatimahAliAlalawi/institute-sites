@if ($partners->isNotEmpty())
    <section class="section">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ \App\Support\HomeSections::text('partners', 'eyebrow', 'شركاء النجاح') }}</span>
                <h2>{{ \App\Support\HomeSections::text('partners', 'title', 'شركاؤنا') }}</h2>
                <div class="divider"></div>
            </div>
            @include('partials.partners')
        </div>
    </section>
@endif
