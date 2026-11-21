@extends('layouts.site')

@section('title', $category ? \App\Models\Program::CATEGORIES[$category] : 'البرامج التدريبية')

@section('content')
    @include('partials.page-head', [
        'title' => $category ? \App\Models\Program::CATEGORIES[$category] : 'البرامج التدريبية',
        'crumb' => 'البرامج التدريبية',
        'subtitle' => 'اختر البرنامج المناسب لك وسجّل اهتمامك بخطوة واحدة',
    ])

    <section class="section">
        <div class="container">
            <div class="filters">
                @if ($planCount)
                    <a href="{{ route('programs.plan') }}" class="plan-banner">
                        <i class="fa-regular fa-calendar-check"></i>
                        <span><b>الخطة التدريبية للأفراد</b> — دورات معتمدة بمواعيد محددة لثلاثة أشهر. اختر الدورة والموعد المناسب لك.</span>
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                @endif
                <div class="tabs">
                    <a href="{{ route('programs.index', array_filter(['q' => $q])) }}" @class(['active' => ! $category])>الكل ({{ $counts->sum() }})</a>
                    @foreach (\App\Models\Program::CATEGORIES as $key => $label)
                        @if ($counts->has($key))
                            <a href="{{ route('programs.index', array_filter(['category' => $key, 'q' => $q])) }}" @class(['active' => $category === $key])>{{ $label }} ({{ $counts[$key] }})</a>
                        @endif
                    @endforeach
                </div>
                <form class="search" method="get" action="{{ route('programs.index') }}">
                    @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
                    <input type="search" name="q" value="{{ $q }}" placeholder="ابحث عن برنامج...">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            @if ($programs->isEmpty())
                <div class="empty">لا توجد برامج مطابقة حاليًا.</div>
            @else
                <div class="grid-3">
                    @foreach ($programs as $program)
                        @include('partials.program-card')
                    @endforeach
                </div>
                {{ $programs->links() }}
            @endif
        </div>
    </section>
@endsection
