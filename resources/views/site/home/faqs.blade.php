@if ($faqs->isNotEmpty())
    <section class="section alt">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ \App\Support\HomeSections::text('faqs', 'eyebrow', 'لديك سؤال؟') }}</span>
                <h2>{{ \App\Support\HomeSections::text('faqs', 'title', 'الأسئلة الشائعة') }}</h2>
                <div class="divider"></div>
            </div>
            @include('partials.faqs')
        </div>
    </section>
@endif
