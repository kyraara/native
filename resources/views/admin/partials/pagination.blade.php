@if ($paginator->hasPages())
    <nav class="ui-pagination" role="navigation" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="ui-page is-disabled" aria-disabled="true">‹ Sebelumnya</span>
        @else
            <a class="ui-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Sebelumnya</a>
        @endif

        <span class="ui-page-numbers">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ui-page is-gap" aria-hidden="true">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="ui-page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="ui-page" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </span>

        @if ($paginator->hasMorePages())
            <a class="ui-page" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya ›</a>
        @else
            <span class="ui-page is-disabled" aria-disabled="true">Berikutnya ›</span>
        @endif
    </nav>
@endif
