<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">{{ \App\Support\HomeSections::text('programs', 'eyebrow', 'برامجنا التدريبية') }}</span>
            <h2>{{ \App\Support\HomeSections::text('programs', 'title', 'برامج تؤهلك لسوق العمل') }}</h2>
            <div class="divider"></div>
        </div>
        @if ($programs->isEmpty())
            <div class="empty">ستُضاف البرامج قريبًا.</div>
        @else
            <div class="grid-3">
                @foreach ($programs as $program)
                    @include('partials.program-card')
                @endforeach
            </div>
        @endif
        <div class="center" style="margin-top:36px"><a href="{{ route('programs.index') }}" class="btn btn-primary">كل البرامج</a></div>
    </div>
</section>
