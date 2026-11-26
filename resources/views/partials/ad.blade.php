@php
  $slotInfo = \Illuminate\Support\Facades\Cache::remember('vox.slots', 3600, fn () => \App\Models\AdSlot::all()->keyBy('code'));
  $s = $slotInfo[$pos] ?? null;
  $c = ($runningAds ?? collect())->get($pos);
  if ($c && ! request()->attributes->get('vox.bot') && ! auth()->check()) { \App\Models\Campaign::whereKey($c->id)->increment('impressions'); }
@endphp
@if (setting('ads_on') && $s)
<aside class="ad ad-{{ $pos }}" aria-label="Espace publicitaire {{ $s->name }}">
  <span class="ad-tag">Publicité</span>
  @if ($c)
    <a class="ad-live" href="{{ route('campaign.click', $c) }}" rel="sponsored noopener" target="_blank"><span class="adv">{{ $c->advertiser }}</span><span class="ad-title">{{ $c->title }}</span><span class="ad-cta">{{ $c->cta }}</span></a>
  @else
    <a class="ad-empty" href="{{ route('annoncer') }}"><span>Votre publicité ici</span><small>{{ $s->name }} · {{ $s->size }}</small></a>
  @endif
</aside>
@endif
