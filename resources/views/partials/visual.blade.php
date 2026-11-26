@php($hue = $a->hue())
@if ($a->cover && $a->cover->url())
<div class="art-visual has-img {{ $size ?? '' }}"><img src="{{ $a->cover->url() }}" alt="{{ $a->cover->alt ?: $a->title }}" loading="lazy" decoding="async" width="{{ $a->cover->width ?: 1200 }}" height="{{ $a->cover->height ?: 750 }}"></div>
@else
<div class="art-visual v{{ $hue }} {{ $size ?? '' }}" role="img" aria-label="Illustration : {{ $a->title }}"><span class="ring"></span><span class="lbl">{{ $a->theme?->name }}</span></div>
@endif
