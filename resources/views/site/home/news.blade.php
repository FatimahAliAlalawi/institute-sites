@if ($posts->isNotEmpty())
    <section class="section alt">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ \App\Support\HomeSections::text('news', 'eyebrow', 'المركز الإعلامي') }}</span>
                <h2>{{ \App\Support\HomeSections::text('news', 'title', 'آخر الأخبار') }}</h2>
                <div class="divider"></div>
            </div>
            <div class="grid-3">
                @foreach ($posts as $post)
                    @include('partials.post-card')
                @endforeach
            </div>
        </div>
    </section>
@endif
