@extends('layouts.site', ['page' => 'article', 'activeRub' => $article->rubrique?->slug])
@section('content')
<article class="ap">
  <header class="wrap ap-head">
    <nav class="crumbs" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a>@if ($article->rubrique)<span>/</span><a href="{{ route('rubrique', $article->rubrique) }}">{{ $article->rubrique->name }}</a>@endif
      @if ($article->sub && $article->rubrique)<span>/</span><a href="{{ route('rubrique', [$article->rubrique, 'sous' => $article->sub]) }}" class="crumb-btn">{{ $article->sub }}</a>@endif</nav>
    <p class="ap-kicker"><span class="nature {{ $article->nature === 'Opinion' ? 'op' : '' }}">{{ $article->label() }}</span>@if ($article->theme)<span class="sep">·</span><a href="{{ route('theme', $article->theme) }}">{{ $article->theme->name }}</a>@endif @if ($article->country)<span class="sep">·</span><span>{{ $article->country }}</span>@endif</p>
    @if ($preview)<p class="preview-note" role="status">Aperçu réservé à la rédaction : cet article est {{ $article->status === 'programmé' ? 'programmé pour le '.fr_date($article->published_at).' à '.$article->published_at->format('H\hi') : 'en '.$article->status }} et n’est pas visible des lecteurs. <a href="{{ route('admin.articles.edit', $article->id) }}">Modifier</a></p>@endif
    <h1>{{ $article->title }}</h1>
    <p class="ap-chapo">{{ $article->chapo }}</p>
    @if ($article->nature === 'Opinion')<p class="op-note">Ce texte est une tribune : il exprime le point de vue de son auteur, pas celui de la rédaction.</p>@endif
    @if ($article->is_sponsored)<p class="op-note">Contenu sponsorisé · Partenariat</p>@endif
    <div class="ap-by">
      <span class="av" aria-hidden="true">{{ initials($article->author?->name ?? 'VTG') }}</span>
      <div>@if ($article->author)<a href="{{ route('auteur', $article->author) }}" class="by-name" rel="author">{{ $article->author->name }}</a>@else<span class="by-name">La rédaction</span>@endif
        <div class="meta">Publié le <time datetime="{{ $article->published_at?->toAtomString() }}">{{ fr_date($article->published_at) }}</time>@if ($article->updated_at && $article->published_at && $article->updated_at->gt($article->published_at->copy()->addDay())) · Mis à jour le <time datetime="{{ $article->updated_at->toAtomString() }}">{{ fr_date($article->updated_at) }}</time>@endif · {{ $article->read_time }} min de lecture</div></div>
      <button class="ap-share" type="button" data-share data-title="{{ $article->title }}" data-url="{{ $article->url() }}">{!! svg_icon('link') !!}<span>Partager</span></button>
    </div>
    <div class="share-row" aria-label="Partager sur les réseaux">
      @php($u = urlencode($article->url()))
      <a href="https://www.facebook.com/sharer/sharer.php?u={{ $u }}" target="_blank" rel="noopener">Facebook</a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $u }}" target="_blank" rel="noopener">LinkedIn</a>
      <a href="https://wa.me/?text={{ urlencode($article->title.' '.$article->url()) }}" target="_blank" rel="noopener">WhatsApp</a>
      <a href="https://x.com/intent/post?url={{ $u }}&text={{ urlencode($article->title) }}" target="_blank" rel="noopener">X</a>
      <a href="mailto:?subject={{ rawurlencode($article->title) }}&body={{ rawurlencode($article->url()) }}">E-mail</a>
    </div>
    @if ($article->status === 'brouillon')<p class="draft-note">Brouillon : cet article n’est pas visible par les lecteurs.</p>@endif
  </header>
  <div class="wrap">@include('partials.visual', ['a' => $article, 'size' => 'hero'])</div>
  <div class="wrap ap-grid">
    @php($toc = $article->toc())
    @if ($toc)
    <aside class="ap-toc" aria-label="Sommaire">
      <p class="toc-lbl">Sommaire</p>
      @foreach ($toc as $i => $t)<a href="#blk-{{ $t['i'] }}" class="{{ $i === 0 ? 'on' : '' }}" data-toc="blk-{{ $t['i'] }}">{{ $t['h'] }}</a>@endforeach
    </aside>
    @endif
    <div class="ap-main">
      @if ($article->verdict)
        <aside class="verdict v-{{ \Illuminate\Support\Str::slug($article->verdict['label']) }}"><span class="vd-lbl">Notre verdict</span><span class="vd-v">{{ $article->verdict['label'] }}</span><p>{{ $article->verdict['text'] }}</p></aside>
      @endif
      @if ($article->fiche)
        <aside class="fiche"><h2>Fiche de l’innovation</h2>
          <dl>@foreach (['statut' => 'Statut', 'porteur' => 'Porteur', 'pays' => 'Pays', 'domaine' => 'Domaine', 'debut' => 'Début', 'licence' => 'Licence'] as $k => $l)@if (!empty($article->fiche[$k]))<div><dt>{{ $l }}</dt><dd>{{ $article->fiche[$k] }}</dd></div>@endif @endforeach</dl></aside>
      @endif
      @if ($article->startup)
        <aside class="fiche"><h2>Fiche de la startup</h2>
          <dl>@foreach (['secteur' => 'Secteur', 'pays' => 'Pays', 'creation' => 'Création', 'stade' => 'Stade', 'equipe' => 'Équipe'] as $k => $l)@if (!empty($article->startup[$k]))<div><dt>{{ $l }}</dt><dd>{{ $article->startup[$k] }}</dd></div>@endif @endforeach</dl></aside>
      @endif
      <div class="prose">
        @php($blocks = $article->blocks())
        @foreach ($blocks as $i => $b)
          @if (!empty($b['p']))<p class="{{ $i === 0 ? 'lead' : '' }}">{{ $b['p'] }}</p>
          @elseif (!empty($b['h']))<h2 id="blk-{{ $i }}">{{ $b['h'] }}</h2>
          @elseif (!empty($b['q']))<blockquote><p>« {{ $b['q'] }} »</p>@if (!empty($b['who']))<cite>{{ $b['who'] }}</cite>@endif</blockquote>
          @elseif (!empty($b['qa']))<div class="qa"><p class="q">{{ $b['qa'][0] }}</p><p class="a">{{ $b['qa'][1] }}</p></div>
          @endif
          @if ($i === 1 && count($blocks) > 3)@include('partials.ad', ['pos' => 'inarticle'])@endif
        @endforeach
      </div>
      @if ($article->sources)<section class="sources"><h2>Sources</h2><ul>@foreach ($article->sources as $s)<li>{{ $s }}</li>@endforeach</ul></section>@endif
      @if ($article->tags)<div class="tags-row">@foreach ($article->tags as $t)<a href="{{ route('recherche', ['q' => $t]) }}" class="tag-pill" rel="tag">#{{ $t }}</a>@endforeach</div>@endif
      <p class="report">Vous avez repéré une erreur ? <a href="{{ route('contact', ['objet' => 'Correction : '.$article->title]) }}">Signalez-la à la rédaction</a>. Les erreurs significatives sont corrigées de manière transparente.</p>
      @if ($article->author)
      <section class="author-box">
        <span class="av lg" aria-hidden="true">{{ initials($article->author->name) }}</span>
        <div><p class="meta mb-1">Écrit par</p><a href="{{ route('auteur', $article->author) }}" class="by-name">{{ $article->author->name }}</a>
          <p class="muted mb-0">{{ $article->author->bio }}</p></div>
      </section>
      @endif

      @if (setting('comments_on'))
      <section class="comments" id="commentaires" aria-labelledby="com-t">
        <h2 id="com-t">Commentaires <span class="muted">({{ $comments->count() + $comments->sum(fn ($c) => $c->replies->count()) }})</span></h2>
        <ul class="com-list">
          @forelse ($comments as $c)
            <li><span class="av sm" aria-hidden="true">{{ initials($c->name) }}</span><div><strong>{{ $c->name }}</strong> <span class="meta">· {{ fr_date($c->created_at) }}</span><p class="mb-0">{{ $c->text }}</p></div></li>
            @foreach ($c->replies as $r)
              <li class="reply"><span class="av sm" aria-hidden="true">{{ initials($r->name) }}</span><div><strong>{{ $r->name }}</strong> <span class="meta">· {{ fr_date($r->created_at) }}</span><p class="mb-0">{{ $r->text }}</p></div></li>
            @endforeach
          @empty
            <li class="muted">Soyez le premier à réagir.</li>
          @endforelse
        </ul>
        @if (session('comment_sent'))
          <p class="ok-t" role="status">{{ session('comment_sent') === 'published' ? 'Merci ! Votre commentaire est publié.' : 'Merci ! Votre commentaire apparaîtra après relecture par la rédaction.' }} <a class="link-like" href="#cm-form">Écrire un autre commentaire</a></p>
        @endif
        <form class="com-form" id="cm-form" method="POST" action="{{ route('comment.store', $article) }}" novalidate>
          @csrf @include('partials.hp')
          @php($e = $errors->comment)
          <div class="row g-2"><div class="col-sm-6"><label class="form-label" for="cm-n">Nom</label><input id="cm-n" name="name" class="form-control {{ $e->has('name') ? 'is-bad' : '' }}" value="{{ old('name') }}" required maxlength="80" autocomplete="name">@if ($e->has('name'))<div class="field-err">{{ $e->first('name') }}</div>@endif</div>
            <div class="col-sm-6"><label class="form-label" for="cm-e">E-mail (non publié)</label><input id="cm-e" name="email" type="email" class="form-control {{ $e->has('email') ? 'is-bad' : '' }}" value="{{ old('email') }}" required autocomplete="email">@if ($e->has('email'))<div class="field-err">{{ $e->first('email') }}</div>@endif</div></div>
          <label class="form-label mt-2" for="cm-t">Votre commentaire</label><textarea id="cm-t" name="text" rows="3" class="form-control {{ $e->has('text') ? 'is-bad' : '' }}" required maxlength="2000">{{ old('text') }}</textarea>@if ($e->has('text'))<div class="field-err">{{ $e->first('text') }}</div>@endif
          <button class="btn btn-ink mt-3" type="submit">Publier le commentaire</button>
          <p class="note mt-2 mb-0">Les commentaires sont relus avant publication. Votre e-mail sert uniquement à vous prévenir d’une réponse.</p>
        </form>
      </section>
      @endif
    </div>
    @include('partials.sidebar')
  </div>
  @if ($next)
  <a class="next-read" href="{{ $next->url() }}"><span class="wrap nr-in"><span class="nr-lbl">À lire ensuite · {{ $next->rubrique?->name }}</span><span class="nr-t">{{ $next->title }}</span>{!! svg_icon('arrow') !!}</span></a>
  @endif
  @if ($related->count())
  <section class="wrap block">
    <header class="bh small"><div><h2>Dans la même veine</h2></div></header>
    <div class="grid-3">@foreach ($related as $a)@include('partials.card', ['a' => $a])@endforeach</div>
  </section>
  @endif
</article>
@endsection
