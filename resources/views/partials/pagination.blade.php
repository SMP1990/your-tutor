{{-- Simple previous / next pagination in the site style. Usage: $items->links('partials.pagination') --}}
@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="pagination">
        <ul>
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination__link pagination__link--wide" aria-disabled="true">‹ Prev</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination__link pagination__link--wide">‹ Prev</a>
                @endif
            </li>
            <li class="pagination__gap">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</li>
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination__link pagination__link--wide">Next ›</a>
                @else
                    <span class="pagination__link pagination__link--wide" aria-disabled="true">Next ›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
