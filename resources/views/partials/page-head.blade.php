<section class="page-head">
    <div class="container">
        <div class="crumbs"><a href="{{ route('home') }}">الرئيسية</a> / {{ $crumb ?? $title }}</div>
        <h1>{{ $title }}</h1>
        @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
    </div>
</section>
