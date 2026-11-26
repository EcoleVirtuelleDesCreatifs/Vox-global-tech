@if ($paginator->hasPages())
<nav class="pager" aria-label="Pagination">
  @if ($paginator->onFirstPage())
    <span class="btn btn-ghost disabled" aria-disabled="true">Précédent</span>
  @else
    <a class="btn btn-ghost" href="{{ $paginator->previousPageUrl() }}" rel="prev">Précédent</a>
  @endif
  <span>Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}</span>
  @if ($paginator->hasMorePages())
    <a class="btn btn-ghost" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant</a>
  @else
    <span class="btn btn-ghost disabled" aria-disabled="true">Suivant</span>
  @endif
</nav>
@endif
