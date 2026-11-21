@extends('layouts.site')

@section('title', $course->title)
@section('description', $course->summary)
@if ($course->thumbnail)
    @section('og_image', media_url($course->thumbnail))
@endif

@section('content')
    @php($rating = $reviews->avg('rating'))
    <section class="course-hero">
        <div class="container">
            <div>
                <div class="crumbs">
                    <a href="{{ route('home') }}">الرئيسية</a> / <a href="{{ route('courses.index') }}">الدورات</a>
                    @if ($course->category) / <a href="{{ route('courses.index', ['category' => $course->category->slug]) }}">{{ $course->category->name }}</a>@endif
                </div>
                <h1>{{ $course->title }}</h1>
                @if ($course->summary)<p class="lead">{{ $course->summary }}</p>@endif
                <div class="facts">
                    @if ($reviews->count())
                        <span>@include('partials.stars', ['rating' => $rating]) <b>{{ number_format($rating, 1) }}</b> ({{ $reviews->count() }} تقييم)</span>
                    @endif
                    <span><i class="fa-solid fa-users"></i> <b>{{ $counts['students'] }}</b> طالب</span>
                    @if ($course->instructor)<span><i class="fa-solid fa-chalkboard-user"></i> المدرب: <b>{{ $course->instructor->name }}</b></span>@endif
                    <span><i class="fa-solid fa-calendar"></i> آخر تحديث {{ $course->updated_at->translatedFormat('F Y') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top:40px">
        <div class="container course-layout">
            <div>
                @if ($course->learn_lines)
                    <div class="panel">
                        <h2>ماذا ستتعلم؟</h2>
                        <ul class="learn-grid">
                            @foreach ($course->learn_lines as $line)<li>{{ $line }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="panel">
                    <h2>محتوى الدورة</h2>
                    <p class="muted">{{ $curriculum->count() }} وحدة · {{ $counts['lessons'] }} درس · {{ $counts['quizzes'] }} اختبار</p>
                    @if ($curriculum->isEmpty())
                        <div class="empty">سيُضاف محتوى الدورة قريبًا.</div>
                    @else
                        <div class="curriculum">
                            @foreach ($curriculum as $topic)
                                <details @if ($loop->first) open @endif>
                                    <summary>{{ $topic->title }} <small>{{ $topic->items->count() }} عنصر</small></summary>
                                    @foreach ($topic->items as $item)
                                        @php($m = $item['model'])
                                        @if ($item['type'] === 'lesson' && ($m->is_preview || $progress !== null))
                                            <a class="item" href="{{ route('learn.lesson', [$course, $m]) }}">
                                        @else
                                            <div class="item">
                                        @endif
                                            <i class="type fa-regular {{ $item['type'] === 'live' ? 'fa-calendar-check' : ($item['type'] === 'quiz' ? 'fa-circle-question' : ($m->video_url ? 'fa-circle-play' : 'fa-file-lines')) }}"></i>
                                            {{ $m->title }}
                                            <span class="end">
                                                @if ($item['type'] === 'lesson' && $m->is_preview && $progress === null)<span class="tag">معاينة مجانية</span>@endif
                                                @if ($item['type'] === 'quiz')<span>{{ $m->questions()->count() }} سؤال</span>@endif
                                                @if ($item['type'] === 'live')<span class="tag" style="background:#ece9fb;color:var(--primary)">مباشر · {{ $m->starts_at->translatedFormat('j M g:i a') }}</span>@endif
                                                @if ($item['type'] === 'lesson' && $m->duration)<span>{{ $m->duration }}</span>@endif
                                                @if ($progress === null && ! ($item['type'] === 'lesson' && $m->is_preview))<i class="fa-solid fa-lock"></i>@endif
                                            </span>
                                        @if ($item['type'] === 'lesson' && ($m->is_preview || $progress !== null))
                                            </a>
                                        @else
                                            </div>
                                        @endif
                                    @endforeach
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($course->description)
                    <div class="panel">
                        <h2>وصف الدورة</h2>
                        <div class="prose">{{ $course->description }}</div>
                    </div>
                @endif

                @if ($course->requirement_lines || $course->target_audience)
                    <div class="panel">
                        @if ($course->requirement_lines)
                            <h2>المتطلبات</h2>
                            <ul class="check-list" style="margin-bottom:20px">
                                @foreach ($course->requirement_lines as $line)<li>{{ $line }}</li>@endforeach
                            </ul>
                        @endif
                        @if ($course->target_audience)
                            <h2>لمن هذه الدورة؟</h2>
                            <p>{{ $course->target_audience }}</p>
                        @endif
                    </div>
                @endif

                @if ($course->instructor)
                    <div class="panel">
                        <h2>المدرب</h2>
                        <div class="instructor-box">
                            @if ($course->instructor->photo)
                                <img class="avatar lg" src="{{ media_url($course->instructor->photo) }}" alt="">
                            @else
                                <span class="avatar lg">{{ mb_substr($course->instructor->name, 0, 1) }}</span>
                            @endif
                            <div>
                                <h3 style="margin:0"><a href="{{ route('instructors.show', $course->instructor) }}">{{ $course->instructor->name }}</a></h3>
                                <div class="muted">{{ $course->instructor->title }}</div>
                                <p class="prose" style="margin-top:8px">{{ $course->instructor->bio }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="panel" id="reviews">
                    <h2>تقييمات الطلاب</h2>
                    @if ($progress !== null)
                        <form method="post" action="{{ route('courses.review', $course) }}" class="form" style="margin-bottom:20px;background:var(--soft2);padding:18px;border-radius:14px">
                            @csrf
                            <b>{{ $myReview ? 'عدّل تقييمك' : 'قيّم الدورة' }}</b>
                            <div class="rating-input">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="rating" id="r{{ $i }}" value="{{ $i }}" @checked((int) old('rating', $myReview?->rating) === $i)>
                                    <label for="r{{ $i }}" title="{{ $i }}"><i class="fa-solid fa-star"></i></label>
                                @endfor
                            </div>
                            @error('rating')<div class="error">{{ $message }}</div>@enderror
                            <textarea name="comment" placeholder="اكتب رأيك في الدورة (اختياري)" style="min-height:80px">{{ old('comment', $myReview?->comment) }}</textarea>
                            <div><button class="btn btn-primary" type="submit">إرسال التقييم</button></div>
                        </form>
                    @endif
                    @forelse ($reviews as $review)
                        <div class="review">
                            <span class="avatar">{{ $review->user?->initials }}</span>
                            <div>
                                <b>{{ $review->user?->name }}</b>
                                @include('partials.stars', ['rating' => $review->rating])
                                <span class="muted" style="font-size:13px">{{ $review->created_at->diffForHumans() }}</span>
                                @if ($review->comment)<p class="prose" style="margin:6px 0 0">{{ $review->comment }}</p>@endif
                            </div>
                        </div>
                    @empty
                        <p class="muted">لا توجد تقييمات بعد.</p>
                    @endforelse
                </div>
            </div>

            <aside>
                <div class="enroll-box">
                    @php($intro = \App\Support\Video::parse($course->intro_video))
                    <div class="media">
                        @if ($intro && $intro['type'] === 'iframe')
                            <iframe src="{{ $intro['src'] }}" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture" title="فيديو تعريفي"></iframe>
                        @elseif ($intro)
                            <video src="{{ $intro['src'] }}" controls preload="metadata"></video>
                        @elseif ($course->thumbnail)
                            <img src="{{ media_url($course->thumbnail) }}" alt="{{ $course->title }}">
                        @else
                            {{ $course->title }}
                        @endif
                    </div>
                    <div class="inner">
                        @if ($progress !== null)
                            <div class="progress"><span style="width:{{ $progress['percent'] }}%"></span></div>
                            <div class="progress-label"><span>تقدمك</span><b>{{ $progress['percent'] }}%</b></div>
                            <a href="{{ route('learn.start', $course) }}" class="btn btn-primary btn-block">{{ $progress['done'] ? 'تابع التعلّم' : 'ابدأ التعلّم' }}</a>
                            @if ($cert = auth()->user()->certificates()->where('course_id', $course->id)->first())
                                <a href="{{ route('certificates.show', $cert->code) }}" class="btn btn-outline btn-block" style="margin-top:10px"><i class="fa-solid fa-award"></i> عرض الشهادة</a>
                            @endif
                        @else
                            <div class="big-price">
                                {{ $course->price_label }}
                                @if ($course->has_discount)<del>{{ number_format((float) $course->price, 0) }} ر.س</del>@endif
                            </div>
                            @if ($course->has_discount && $course->sale_ends_at)
                                <div class="pill pending" style="margin-top:8px"><i class="fa-regular fa-clock"></i> العرض ينتهي {{ $course->sale_ends_at->diffForHumans() }}</div>
                            @endif
                            @if ($enrollment?->status === 'pending')
                                <div class="alert alert-success" style="margin:14px 0 0">طلبك قيد المراجعة — سيتم تفعيل الدورة بعد تأكيد الدفع.</div>
                            @else
                                <form method="post" action="{{ route('courses.enroll', $course) }}" style="margin-top:14px">
                                    @csrf
                                    <button class="btn btn-primary btn-block" type="submit">{{ $course->is_free ? 'اشترك مجانًا' : 'اشترك الآن' }}</button>
                                </form>
                                @guest<p class="muted center" style="font-size:13px;margin:8px 0 0">ستحتاج إلى تسجيل الدخول أو إنشاء حساب</p>@endguest
                            @endif
                        @endif
                        <ul>
                            <li><i class="fa-solid fa-signal"></i> المستوى: {{ $course->level_label }}</li>
                            <li><i class="fa-regular fa-circle-play"></i> {{ $counts['lessons'] }} درس</li>
                            @if ($counts['quizzes'])<li><i class="fa-regular fa-circle-question"></i> {{ $counts['quizzes'] }} اختبار</li>@endif
                            @if ($course->duration_text)<li><i class="fa-regular fa-clock"></i> المدة: {{ $course->duration_text }}</li>@endif
                            @if ($course->daily_release)
                                <li><i class="fa-solid fa-calendar-day"></i> دورة مسجلة: {{ $course->lessons_per_day > 1 ? $course->lessons_per_day.' دروس' : 'درس واحد' }} يوميًا ({{ \App\Support\DailyRelease::label(\App\Support\DailyRelease::daysFor(auth()->user())) }})</li>
                            @else
                                <li><i class="fa-solid fa-infinity"></i> وصول كامل للمحتوى</li>
                            @endif
                            @if ($course->certificate_enabled)<li><i class="fa-solid fa-award"></i> شهادة إتمام قابلة للتحقق</li>@endif
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section alt">
            <div class="container">
                <div class="section-head"><h2>دورات ذات صلة</h2><div class="divider"></div></div>
                <div class="grid-3">
                    @foreach ($related as $course)
                        @include('partials.course-card')
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
