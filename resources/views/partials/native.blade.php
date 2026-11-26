@php($n = ($runningAds ?? collect())->get('native'))
@if ($n && setting('ads_on'))
<a class="native {{ $class ?? '' }}" href="{{ route('campaign.click', $n) }}" rel="sponsored noopener" target="_blank"><span class="nat-tag">Partenaire · {{ $n->advertiser }}</span><span class="nat-t">{{ $n->title }}</span><span class="nat-cta">{{ $n->cta }} {!! svg_icon('arrow') !!}</span></a>
@endif
