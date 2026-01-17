@if ($paginator->hasPages())
    <ul class="pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                <span class="page-link" aria-hidden="true" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                    <i class="icon-base ti tabler-chevron-left" style="font-size: 0.8rem; font-weight: 500;"></i>
                </span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link ajax-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                    <i class="icon-base ti tabler-chevron-left" style="font-size: 0.8rem; font-weight: 500;"></i>
                </a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="page-item disabled" aria-disabled="true"><span class="page-link" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">{{ $element }}</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link ajax-pagination-link" href="{{ $url }}" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link ajax-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                    <i class="icon-base ti tabler-chevron-right" style="font-size: 0.8rem; font-weight: 500;"></i>
                </a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                <span class="page-link" aria-hidden="true" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                    <i class="icon-base ti tabler-chevron-right" style="font-size: 0.8rem; font-weight: 500;"></i>
                </span>
            </li>
        @endif
    </ul>
@endif

