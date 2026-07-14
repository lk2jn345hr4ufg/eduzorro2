@if ($paginator->hasPages())
    <nav class="pagination" role="navigation">
        @if ($paginator->onFirstPage())
            <span class="page disabled">&laquo;</span>
        @else
            <a class="page" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
        @else
            <span class="page disabled">&raquo;</span>
        @endif
    </nav>
@endif
