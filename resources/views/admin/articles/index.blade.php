@extends('layouts.admin')
@section('title', 'Articles')
@section('content')
<div class="adm-head"><div><h1>Articles</h1><p class="muted mb-0">{{ $total }} contenus · articles, interviews écrites, portraits, projets documentés, dossiers, fact-checks.</p></div>@can('draft')<a class="btn btn-red" href="{{ route('admin.articles.create') }}">Nouvel article</a>@endcan</div>
<form class="adm-filters" method="GET" data-autosubmit>
  <label for="aq" class="visually-hidden">Rechercher</label><input id="aq" name="q" class="form-control" type="search" value="{{ request('q') }}" placeholder="Titre ou auteur…">
  <label for="ar" class="visually-hidden">Rubrique</label><select id="ar" name="rubrique" class="form-select"><option value="">Toutes les rubriques</option>@foreach ($rubriques as $r)<option value="{{ $r->id }}" @selected(request('rubrique') == $r->id)>{{ $r->name }}</option>@endforeach</select>
  <label for="as" class="visually-hidden">Statut</label><select id="as" name="statut" class="form-select"><option value="">Tous les statuts</option>@foreach (['publié' => 'Publiés', 'programmé' => 'Programmés', 'brouillon' => 'Brouillons'] as $k => $l)<option value="{{ $k }}" @selected(request('statut') === $k)>{{ $l }}</option>@endforeach</select>
  <label for="ao" class="visually-hidden">Trier</label><select id="ao" name="tri" class="form-select"><option value="">Plus récents</option><option value="views" @selected(request('tri') === 'views')>Plus lus</option><option value="title" @selected(request('tri') === 'title')>Titre</option></select>
</form>
<form method="POST" action="{{ route('admin.articles.bulk') }}" id="bulk-form" data-bulk>
  @csrf
  <div class="bulk-bar" hidden><strong><span data-bulk-n>0</span> sélectionné(s)</strong>
    @can('publish')<button class="mini-btn" name="do" value="publish">Publier</button><button class="mini-btn" name="do" value="unpublish">Repasser en brouillon</button>@endcan
    @can('edit-others')<button class="mini-btn" name="do" value="feature">Mettre à la une</button><button class="mini-btn" name="do" value="unfeature">Retirer de la une</button><button class="mini-btn danger" name="do" value="delete" data-confirm="Supprimer définitivement les articles sélectionnés ?">Supprimer</button>@endcan
    <button class="mini-btn" type="button" data-bulk-clear>Annuler</button></div>
  <div class="panel p-0"><div class="table-wrap"><table class="tbl">
    <thead><tr><th class="cb"><input class="form-check-input" type="checkbox" data-check-all aria-label="Tout sélectionner"></th><th>Titre</th><th>Type</th><th>Rubrique</th><th>Auteur</th><th>Date</th><th class="num">Lectures</th><th>Statut</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
      @forelse ($articles as $a)
        @php($st = $a->effectiveStatus())
        <tr>
          <td class="cb"><input class="form-check-input" type="checkbox" name="ids[]" value="{{ $a->id }}" aria-label="Sélectionner {{ $a->title }}"></td>
          <td class="t-title"><a href="{{ route('admin.articles.edit', $a->id) }}">{{ $a->title }}</a>@if ($a->is_featured)<span class="une-b">À la une</span>@endif</td>
          <td>{{ \App\Models\Article::TYPES[$a->type] ?? $a->type }}</td><td>{{ $a->rubrique?->name }}</td><td>{{ $a->author?->name }}</td><td class="nowrap">{{ $a->published_at?->format('Y-m-d H:i') }}</td><td class="num">{{ nf($a->views) }}</td>
          <td>@if ($st === 'programmé')<span class="status sched">Programmé</span>@else
            @can('publish')<button type="submit" form="tg-{{ $a->id }}" class="status {{ $st === 'publié' ? 'ok' : 'draft' }}" aria-label="Statut : {{ $st }}. Changer">{{ $st === 'publié' ? 'Publié' : 'Brouillon' }}</button>@else<span class="status {{ $st === 'publié' ? 'ok' : 'draft' }}">{{ ucfirst($st) }}</span>@endcan @endif</td>
          <td class="nowrap"><a class="mini-btn" href="{{ $a->url() }}" target="_blank">Voir</a><a class="mini-btn" href="{{ route('admin.articles.edit', $a->id) }}">Modifier</a>@can('edit-others')<button class="mini-btn danger" type="submit" form="del-{{ $a->id }}" data-confirm="Supprimer « {{ $a->title }} » ?">Supprimer</button>@endcan</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center muted py-4">Aucun article ne correspond.</td></tr>
      @endforelse
    </tbody></table></div></div>
</form>
@foreach ($articles as $a)
  <form id="tg-{{ $a->id }}" method="POST" action="{{ route('admin.articles.toggle', $a->id) }}" hidden>@csrf</form>
  <form id="del-{{ $a->id }}" method="POST" action="{{ route('admin.articles.destroy', $a->id) }}" hidden>@csrf @method('DELETE')</form>
@endforeach
{{ $articles->links() }}
@endsection
