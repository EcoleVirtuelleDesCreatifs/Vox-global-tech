@extends('layouts.admin')
@section('title', $a->exists ? 'Modifier l’article' : 'Nouvel article')
@section('content')
@php
  $st = $a->exists ? $a->effectiveStatus() : 'brouillon';
  $canPublish = auth()->user()->canDo('publish');
  $text = old('text', $a->bodyToText());
@endphp
<form method="POST" action="{{ $a->exists ? route('admin.articles.update', $a->id) : route('admin.articles.store') }}" enctype="multipart/form-data" novalidate data-editor data-siteurl="{{ url('/article') }}">
  @csrf @if ($a->exists) @method('PUT') @endif
  <div class="adm-head"><div><a href="{{ route('admin.articles') }}" class="muted">← Tous les articles</a><h1>{{ $a->exists ? 'Modifier l’article' : 'Nouvel article' }}</h1></div>
    <div class="d-flex gap-2 flex-wrap">
      @if ($a->exists)<a class="btn btn-ghost" href="{{ $a->url() }}" target="_blank">Aperçu</a>@endif
      <button class="btn btn-ghost" type="submit" name="action" value="save">Enregistrer</button>
      @if ($canPublish)
        @if ($st !== 'brouillon')<button class="btn btn-ghost" type="submit" name="action" value="unpublish">Repasser en brouillon</button>@endif
        <button class="btn btn-red" type="submit" name="action" value="publish">{{ $st === 'publié' ? 'Mettre à jour' : 'Publier' }}</button>
      @endif
    </div></div>
  <div class="editor">
    <div class="panel d-grid gap-3">
      <div><label class="form-label" for="e-t">Titre</label><input id="e-t" name="title" class="form-control form-control-lg {{ $errors->has('title') ? 'is-bad' : '' }}" value="{{ old('title', $a->title) }}" required maxlength="200">@error('title')<div class="field-err">{{ $message }}</div>@enderror</div>
      <div><label class="form-label" for="e-c">Chapeau</label><textarea id="e-c" name="chapo" rows="2" class="form-control {{ $errors->has('chapo') ? 'is-bad' : '' }}" maxlength="600">{{ old('chapo', $a->chapo) }}</textarea>@error('chapo')<div class="field-err">{{ $message }}</div>@enderror</div>
      <div><label class="form-label" for="e-x">Contenu</label>
        <p class="note mb-2">Séparez les paragraphes par une ligne vide. « ## » pour un intertitre, « &gt; » pour une citation (« &gt; Texte — Auteur »), « Q : » puis « R : » pour une question-réponse d’interview.</p>
        <textarea id="e-x" name="text" rows="18" class="form-control mono {{ $errors->has('text') ? 'is-bad' : '' }}" data-words="e-wc">{{ $text }}</textarea>
        <div class="d-flex justify-content-between">@error('text')<div class="field-err">{{ $message }}</div>@enderror<span class="meta ms-auto"><span id="e-wc">0</span> mots · ~<span data-read>1</span> min de lecture</span></div></div>
      <div class="fiche-edit">
        <h2 class="h-sm">Référencement (SEO) et partage</h2>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="e-sl">Adresse de la page</label><input id="e-sl" name="slug" class="form-control" value="{{ old('slug', $a->slug) }}" placeholder="générée depuis le titre" pattern="[a-z0-9-]*">@error('slug')<div class="field-err">{{ $message }}</div>@enderror</div>
          <div class="col-md-6"><label class="form-label" for="e-st">Titre pour Google</label><input id="e-st" name="seo_title" class="form-control" value="{{ old('seo_title', $a->seo_title) }}" maxlength="70" placeholder="{{ $a->title }}" data-counter="60"><div class="char-c" data-for="e-st">0 / 60</div></div>
          <div class="col-12"><label class="form-label" for="e-sd">Description pour Google</label><textarea id="e-sd" name="seo_description" rows="2" class="form-control" maxlength="180" placeholder="{{ $a->chapo }}" data-counter="155">{{ old('seo_description', $a->seo_description) }}</textarea><div class="char-c" data-for="e-sd">0 / 155</div></div>
        </div>
        <div class="serp" aria-label="Aperçu dans Google"><span class="serp-url">{{ parse_url(config('app.url'), PHP_URL_HOST) }} › article › <span data-serp-url>{{ $a->slug ?: 'nouvel-article' }}</span></span><span class="serp-t"><span data-serp-t>{{ $a->seoTitle() ?: 'Titre de l’article' }}</span> | {{ setting('name') }}</span><span class="serp-d" data-serp-d>{{ $a->seoDescription() ?: 'Description de l’article.' }}</span></div>
        <p class="note mt-2 mb-0">Généré automatiquement : image de partage 1200×630, données structurées NewsArticle{{ $a->type === 'interview' ? ' + FAQ' : '' }}{{ $a->type === 'factcheck' ? ' + ClaimReview' : '' }}, version Markdown pour les IA, ping IndexNow à la publication.</p>
      </div>
      <div class="fiche-edit"><label class="form-label" for="e-so">Sources (une par ligne)</label><textarea id="e-so" name="sources_text" rows="3" class="form-control" placeholder="Entretien avec…, rapport…, document…">{{ old('sources_text', implode("\n", $a->sources ?? [])) }}</textarea><p class="note mb-0">Les articles sourcés sont mieux cités par les moteurs de réponse IA.</p></div>
      <div class="fiche-edit" data-show-type="projet"><h2 class="h-sm">Fiche de l’innovation</h2>
        <div class="row g-2">@foreach (['statut' => 'Statut', 'porteur' => 'Porteur', 'pays' => 'Pays', 'domaine' => 'Domaine', 'debut' => 'Début', 'licence' => 'Licence'] as $k => $l)<div class="col-sm-6"><label class="form-label" for="fi-{{ $k }}">{{ $l }}</label><input id="fi-{{ $k }}" name="fiche[{{ $k }}]" class="form-control" value="{{ old('fiche.'.$k, $a->fiche[$k] ?? '') }}"></div>@endforeach</div></div>
      <div class="fiche-edit" data-show-type="factcheck"><h2 class="h-sm">Verdict du fact-check</h2>
        <div class="row g-2"><div class="col-sm-4"><label class="form-label" for="vd-l">Verdict</label><select id="vd-l" name="verdict[label]" class="form-select"><option value="">—</option>@foreach (['Faux', 'Trompeur', 'Exagéré', 'Imprécis', 'Plutôt vrai', 'Vrai'] as $v)<option @selected(old('verdict.label', $a->verdict['label'] ?? '') === $v)>{{ $v }}</option>@endforeach</select></div>
          <div class="col-sm-8"><label class="form-label" for="vd-t">Explication</label><input id="vd-t" name="verdict[text]" class="form-control" value="{{ old('verdict.text', $a->verdict['text'] ?? '') }}"></div></div></div>
      <details class="fiche-edit" @if ($a->startup) open @endif><summary class="h-sm">Fiche startup (facultatif)</summary>
        <div class="row g-2 mt-1">@foreach (['secteur' => 'Secteur', 'pays' => 'Pays', 'creation' => 'Création', 'stade' => 'Stade', 'equipe' => 'Équipe'] as $k => $l)<div class="col-sm-4"><label class="form-label" for="su-{{ $k }}">{{ $l }}</label><input id="su-{{ $k }}" name="startup[{{ $k }}]" class="form-control" value="{{ old('startup.'.$k, $a->startup[$k] ?? '') }}"></div>@endforeach</div></details>
    </div>
    <div class="panel d-grid gap-3 align-content-start">
      <div><span class="form-label d-block">Statut</span><span class="status {{ $st === 'publié' ? 'ok' : ($st === 'programmé' ? 'sched' : 'draft') }}">{{ \App\Models\Article::STATUSES[$st] ?? $st }}</span>
        @if ($a->exists)<span class="meta ms-2">{{ nf($a->views) }} lectures · {{ $comments }} commentaire(s)</span>@endif
        <p class="note mt-2 mb-0">Avec une date future, « Publier » programme l’article : il paraîtra seul ce jour-là.@unless ($canPublish) Votre rôle permet d’enregistrer des brouillons ; un rédacteur les publiera.@endunless</p></div>
      <div class="row g-2"><div class="col-7"><label class="form-label" for="e-d">Date de publication</label><input id="e-d" name="date" type="date" class="form-control" value="{{ old('date', $a->published_at?->format('Y-m-d')) }}"></div>
        <div class="col-5"><label class="form-label" for="e-h">Heure</label><input id="e-h" name="time" type="time" class="form-control" value="{{ old('time', $a->published_at?->format('H:i')) }}"></div></div>
      <div><label class="form-label" for="e-ty">Type de contenu</label><select id="e-ty" name="type" class="form-select" data-type-select>@foreach (['article' => 'Article', 'interview' => 'Interview écrite', 'portrait' => 'Portrait', 'projet' => 'Innovation documentée', 'dossier' => 'Dossier / enquête', 'factcheck' => 'Fact-check'] as $k => $l)<option value="{{ $k }}" @selected(old('type', $a->type) === $k)>{{ $l }}</option>@endforeach</select></div>
      <div><label class="form-label" for="e-na">Nature du contenu</label><select id="e-na" name="nature" class="form-select">@foreach (\App\Models\Article::NATURES as $n)<option @selected(old('nature', $a->nature) === $n)>{{ $n }}</option>@endforeach</select><p class="note mt-1 mb-0">La charte impose de distinguer faits, opinions, annonces et hypothèses.</p></div>
      <div><label class="form-label" for="e-r">Rubrique</label><select id="e-r" name="rubrique_id" class="form-select" data-rub-select>@foreach ($rubriques as $r)<option value="{{ $r->id }}" data-subs='@json($r->subs ?? [])' @selected(old('rubrique_id', $a->rubrique_id) == $r->id)>{{ $r->name }}</option>@endforeach</select></div>
      <div><label class="form-label" for="e-s">Sous-rubrique</label><select id="e-s" name="sub" class="form-select" data-sub-select data-current="{{ old('sub', $a->sub) }}"><option value="">Aucune</option></select></div>
      <div><label class="form-label" for="e-th">Thématique</label><select id="e-th" name="theme_id" class="form-select"><option value="">—</option>@foreach ($themes as $t)<option value="{{ $t->id }}" @selected(old('theme_id', $a->theme_id) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
      <div class="row g-2"><div class="col-6"><label class="form-label" for="e-rg">Région</label><select id="e-rg" name="region" class="form-select">@foreach (\App\Models\Article::REGIONS as $r)<option @selected(old('region', $a->region) === $r)>{{ $r }}</option>@endforeach</select></div><div class="col-6"><label class="form-label" for="e-co">Pays</label><input id="e-co" name="country" class="form-control" value="{{ old('country', $a->country) }}"></div></div>
      <div><label class="form-label" for="e-a">Auteur</label><select id="e-a" name="author_id" class="form-select"><option value="">La rédaction</option>@foreach ($authors as $au)<option value="{{ $au->id }}" @selected(old('author_id', $a->author_id) == $au->id)>{{ $au->name }}</option>@endforeach</select></div>
      <div><label class="form-label" for="e-tg">Mots-clés</label><input id="e-tg" name="tags" class="form-control" value="{{ old('tags', implode(', ', $a->tags ?? [])) }}" placeholder="séparés par des virgules"></div>
      <div><span class="form-label d-block">Image de couverture</span>
        <div class="cover-prev" data-cover-prev @if ($a->cover?->url()) style="background-image:url('{{ $a->cover->url() }}')" @endif>@unless ($a->cover?->url())<span>Visuel graphique automatique</span>@endunless</div>
        <input type="file" name="cover_file" accept="image/*" class="form-control mt-2" data-cover-input aria-label="Téléverser une image de couverture">
        @if ($media->count())<select name="cover_id" class="form-select mt-2" aria-label="Choisir dans la médiathèque"><option value="">… ou choisir dans la médiathèque</option>@foreach ($media as $m)<option value="{{ $m->id }}" data-url="{{ $m->url() }}" @selected($a->cover_id == $m->id)>{{ $m->name }}</option>@endforeach</select>@endif
        @if ($a->cover_id)<div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="e-rc" name="remove_cover" value="1"><label class="form-check-label" for="e-rc">Retirer la couverture</label></div>@endif
        @error('cover_file')<div class="field-err">{{ $message }}</div>@enderror</div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="e-u" name="is_featured" value="1" @checked(old('is_featured', $a->is_featured))><label class="form-check-label" for="e-u">Mettre à la une</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="e-sp" name="is_sponsored" value="1" @checked(old('is_sponsored', $a->is_sponsored))><label class="form-check-label" for="e-sp">Contenu sponsorisé (mention « Partenariat »)</label></div>
      @if ($a->exists && (auth()->user()->canDo('edit-others')))<button class="btn btn-ghost danger-t" type="submit" form="del-art" data-confirm="Supprimer définitivement cet article ?">Supprimer l’article</button>@endif
    </div>
  </div>
</form>
@if ($a->exists)<form id="del-art" method="POST" action="{{ route('admin.articles.destroy', $a->id) }}" hidden>@csrf @method('DELETE')</form>@endif
@endsection
