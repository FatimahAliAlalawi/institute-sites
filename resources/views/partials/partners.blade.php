<div class="partners">
    @foreach ($partners as $partner)
        @php($tag = $partner->url ? 'a' : 'div')
        <{{ $tag }} class="partner" @if ($partner->url) href="{{ $partner->url }}" target="_blank" rel="noopener" @endif title="{{ $partner->name }}">
            @if ($partner->logo)
                <img src="{{ media_url($partner->logo) }}" alt="{{ $partner->name }}" loading="lazy">
            @else
                {{ $partner->name }}
            @endif
        </{{ $tag }}>
    @endforeach
</div>
