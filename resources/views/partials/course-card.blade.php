<a href="{{ route('courses.show', $course) }}" class="card course-card">
    <div class="thumb">
        @if ($course->thumbnail)
            <img src="{{ media_url($course->thumbnail) }}" alt="{{ $course->title }}" loading="lazy">
        @else
            <div class="placeholder">{{ $course->title }}</div>
        @endif
        @if ($course->category)<span class="tag">{{ $course->category->name }}</span>@endif
    </div>
    <div class="body">
        @if ($course->instructor)
            <div class="by">
                @if ($course->instructor->photo)
                    <img class="avatar" src="{{ media_url($course->instructor->photo) }}" alt="">
                @else
                    <span class="avatar">{{ mb_substr($course->instructor->name, 0, 1) }}</span>
                @endif
                {{ $course->instructor->name }}
            </div>
        @endif
        <h3>{{ $course->title }}</h3>
        <p>{{ \Illuminate\Support\Str::limit((string) $course->summary, 110) }}</p>
        <div class="meta">
            <span class="chip"><i class="fa-solid fa-signal"></i> {{ $course->level_label }}</span>
            @isset($course->lessons_count)<span class="chip"><i class="fa-regular fa-circle-play"></i> {{ $course->lessons_count }} درس</span>@endisset
            @if ($course->duration_text)<span class="chip"><i class="fa-regular fa-clock"></i> {{ $course->duration_text }}</span>@endif
        </div>
        <div class="foot">
            <span @class(['price', 'free' => $course->is_free])>
                {{ $course->price_label }}
                @if ($course->has_discount)<del>{{ number_format((float) $course->price, 0) }} ر.س</del>@endif
            </span>
            @if (! empty($course->reviews_avg_rating))
                @include('partials.stars', ['rating' => $course->reviews_avg_rating])
            @else
                <span class="more">التفاصيل <i class="fa-solid fa-arrow-left"></i></span>
            @endif
        </div>
    </div>
</a>
