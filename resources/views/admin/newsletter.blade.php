@extends('layouts.admin')
@section('title', 'Newsletter')
@section('content')
@php($sel = array_map('intval', old('articles', [])))
<div class="adm-head"><div><h1>Newsletter</h1><p class="muted mb-0">« La lettre des talents » · {{ nf($subscribers) }} abonné(s) confirmé(s) · taux d’ouverture moyen {{ str_replace('.', ',', $rate) }} %</p></div>
  @unless ($compose)<a class="btn btn-red" href="{{ route('admin.newsletter', ['nouvelle' => 1]) }}">Nouvelle newsletter</a>@endunless</div>

@if ($compose || session('compose'))
<form class="nl-edit mb-4" method="POST" action="{{ route('admin.newsletter.send') }}" data-nl-form>@csrf
  <div class="panel d-grid gap-3">
    <h2>Composer</h2>
    <div><label class="form-label" for="nl-s">Objet</label><input id="nl-s" name="subject" class="form-control" value="{{ old('subject', 'La lettre des talents · '.fr_date(now())) }}" required maxlength="150" data-nl-subject></div>
    <div><label class="form-label" for="nl-i">Introduction</label><textarea id="nl-i" name="intro" rows="4" class="form-control" maxlength="1500" data-nl-intro>{{ old('intro', 'Bonjour, voici les talents, innovations et startups qui ont retenu l’attention de la rédaction cette semaine.') }}</textarea></div>
    <div><span class="form-label d-block">Articles à inclure (dans l’ordre de sélection)</span>
      <div class="nl-pick">@foreach ($articles as $a)<label class="form-check"><input class="form-check-input" type="checkbox" name="articles[]" value="{{ $a->id }}" @checked(in_array($a->id, $sel, true) || (! $sel && $loop->index < 4)) data-nl-art data-title="{{ $a->title }}" data-chapo="{{ \Illuminate\Support\Str::limit($a->chapo, 140) }}" data-kicker="{{ $a->rubrique?->name }}"><span class="form-check-label">{{ $a->title }} <span class="meta">· {{ $a->published_at?->format('d/m') }}</span></span></label>@endforeach</div></div>
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-red" type="submit" name="mode" value="send" data-confirm="Envoyer cette newsletter à {{ nf($subscribers) }} abonné(s) ? L’envoi ne peut pas être annulé." data-confirm-label="Envoyer">Envoyer à {{ nf($subscribers) }} abonné(s)</button>
      <button class="btn btn-ghost" type="submit" name="mode" value="test">M’envoyer un test</button>
      <a class="btn btn-ghost" href="{{ route('admin.newsletter') }}">Annuler</a></div>
    <p class="note mb-0">Chaque envoi contient un lien de désabonnement en un clic (en-tête List-Unsubscribe), un pixel de mesure d’ouverture et des liens suivis. Les e-mails partent par la file d’attente (tâche planifiée).</p>
  </div>
  <div class="panel"><h2>Aperçu</h2>
    <div class="mail">
      <div class="mail-top">@include('partials.logo')</div>
      <p class="mail-subj" data-nl-prev-subject></p><p data-nl-prev-intro style="white-space:pre-line"></p>
      <div data-nl-prev-arts></div>
      <p class="meta mail-foot">Vous recevez cet e-mail car vous êtes abonné à {{ setting('name') }}. Se désabonner.</p>
    </div></div>
</form>
@endif

<div class="panel p-0"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Objet</th><th>Date</th><th class="num">Destinataires</th><th class="num">Ouvertures</th><th class="num">Clics</th><th>État</th></tr></thead>
  <tbody>
  @forelse ($newsletters as $n)
    <tr><td><strong>{{ $n->subject }}</strong><div class="meta">{{ count($n->article_ids ?? []) }} article(s)</div></td>
      <td class="nowrap">{{ ($n->sent_at ?? $n->created_at)?->format('d/m/Y H:i') }}</td>
      <td class="num">{{ nf($n->recipients) }}</td>
      <td class="num">{{ $n->status === 'Envoyée' ? str_replace('.', ',', $n->openRate()).' %' : '—' }}<div class="meta">{{ nf($n->opens) }}</div></td>
      <td class="num">{{ $n->status === 'Envoyée' ? str_replace('.', ',', $n->clickRate()).' %' : '—' }}<div class="meta">{{ nf($n->clicks) }}</div></td>
      <td><span class="status {{ $n->status === 'Envoyée' ? 'ok' : ($n->status === 'Programmée' ? 'sched' : 'draft') }}">{{ $n->status === 'Programmée' ? 'Envoi en cours' : $n->status }}</span></td></tr>
  @empty
    <tr><td colspan="6" class="text-center muted py-4">Aucune newsletter envoyée.</td></tr>
  @endforelse
  </tbody></table></div></div>
@endsection
