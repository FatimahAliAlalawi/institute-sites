<a href="{{ route('news.show', $post) }}" class="card post-card">
    <div class="thumb">
        @if ($post->image)
            <img src="{{ media_url($post->image) }}" alt="{{ $post->title }}" loading="lazy">
        @else
            <div class="placeholder"><i class="fa-regular fa-newspaper fa-2x"></i></div>
        @endif
    </div>
    <div class="body">
        <span class="date">{{ $post->date?->translatedFormat('j F Y') }}</span>
        <h3>{{ $post->title }}</h3>
        <p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 140) }}</p>
        <span class="more">اقرأ المزيد <i class="fa-solid fa-arrow-left"></i></span>
    </div>
</a>
