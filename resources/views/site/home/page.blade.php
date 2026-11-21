@if ($block = \App\Models\Page::where('is_published', true)->find($section['page_id'] ?? 0))
    <section class="section {{ ($section['style'] ?? '') === 'alt' ? 'alt' : '' }}">
        <div class="container">
            @if ($section['show_title'] ?? true)
                <div class="section-head"><h2>{{ $block->title }}</h2><div class="divider"></div></div>
            @endif
            <div class="rich-content">{!! $block->content !!}</div>
        </div>
    </section>
@endif
