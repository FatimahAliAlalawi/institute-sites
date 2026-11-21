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
