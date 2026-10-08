@if($paginator->hasPages())
  <nav class="ra-pager" aria-label="Pagination">
    @if($paginator->previousPageUrl())<a class="ra-btn ra-btn-ghost ra-btn-sm" href="{{ $paginator->previousPageUrl() }}">← Previous</a>@endif
    <span class="ra-status">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    @if($paginator->nextPageUrl())<a class="ra-btn ra-btn-ghost ra-btn-sm" href="{{ $paginator->nextPageUrl() }}">Next →</a>@endif
  </nav>
@endif
