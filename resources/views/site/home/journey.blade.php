@if ($journey = lines(setting('journey')))
    <section class="section">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ \App\Support\HomeSections::text('journey', 'eyebrow', 'من متدرب... إلى موظف') }}</span>
                <h2>{{ \App\Support\HomeSections::text('journey', 'title', 'رحلة المتدرب معنا') }}</h2>
                <div class="divider"></div>
            </div>
            <div class="steps">
                @foreach ($journey as $i => $step)
                    <div class="step"><div class="num">{{ $i + 1 }}</div><h4>{{ $step }}</h4></div>
                @endforeach
            </div>
        </div>
    </section>
@endif
