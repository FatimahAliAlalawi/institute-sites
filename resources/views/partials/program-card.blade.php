<a href="{{ route('programs.show', $program) }}" class="card">
    <div class="thumb">
        @if ($program->image)
            <img src="{{ media_url($program->image) }}" alt="{{ $program->title }}" loading="lazy">
        @else
            <div class="placeholder">{{ $program->title }}</div>
        @endif
        <span class="tag">{{ $program->category_label }}</span>
    </div>
    <div class="body">
        <h3>{{ $program->title }}</h3>
        <p>{{ $program->summary }}</p>
        @if ($program->schedule_days || $program->schedule_time || $program->trainer_name)
            <div class="sched">
                @if ($program->schedule_days)<span><i class="fa-regular fa-calendar"></i> {{ $program->schedule_days }}</span>@endif
                @if ($program->schedule_time)<span><i class="fa-regular fa-clock"></i> {{ $program->schedule_time }}</span>@endif
                @if ($program->trainer_name)<span><i class="fa-solid fa-chalkboard-user"></i> {{ $program->trainer_name }}</span>@endif
            </div>
        @endif
        <div class="meta">
            @if ($program->duration)<span class="chip"><i class="fa-regular fa-clock"></i> {{ $program->duration }}</span>@endif
            @if ($program->has_offer)
                <span class="chip free">{{ $program->offer_price_label }} <s class="old-price">{{ $program->price_label }}</s></span>
            @elseif ($program->price_label)
                <span @class(['chip', 'free' => (float) $program->price == 0])>{{ $program->price_label }}</span>
            @endif
            @if ($program->start_date && $program->start_date->isFuture())<span class="chip"><i class="fa-regular fa-calendar"></i> {{ $program->start_date->translatedFormat('j F') }}</span>@endif
            <span class="chip">{{ $program->mode }}</span>
        </div>
        <span class="more">التفاصيل والتسجيل <i class="fa-solid fa-arrow-left"></i></span>
    </div>
</a>
