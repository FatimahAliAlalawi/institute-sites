@if ($paginator->hasPages())
    <nav class="pagination" aria-label="الصفحات">
        @if ($paginator->onFirstPage())
            <span class="disabled">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
        @endif

        @if (method_exists($paginator, 'getUrlRange'))
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="current">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
        @else
            <span class="disabled">التالي</span>
        @endif
    </nav>
@endif
