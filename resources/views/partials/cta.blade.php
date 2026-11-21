<section class="section">
    <div class="container">
        <div class="cta">
            <div>
                <h2>{{ setting('cta_title', 'جاهز تبدأ رحلتك؟') }}</h2>
                <p>{{ setting('cta_text') }}</p>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="{{ route('register') }}" class="btn btn-light">سجّل الآن</a>
                <a href="{{ route('contact') }}" class="btn btn-ghost">تواصل معنا</a>
            </div>
        </div>
    </div>
</section>
