@if ($courses->isNotEmpty())
    <section class="section">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ \App\Support\HomeSections::text('courses', 'eyebrow', 'المنصة التعليمية') }}</span>
                <h2>{{ \App\Support\HomeSections::text('courses', 'title', 'دورات تدريبية — تعلّم بالسرعة التي تناسبك') }}</h2>
                <div class="divider"></div>
            </div>
            <div class="grid-3">
                @foreach ($courses as $course)
                    @include('partials.course-card')
                @endforeach
            </div>
            <div class="center" style="margin-top:36px"><a href="{{ route('courses.index') }}" class="btn btn-primary">كل الدورات</a></div>
        </div>
    </section>
@endif
