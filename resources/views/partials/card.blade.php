@php($variant = $variant ?? 'std')
<article class="card-a {{ $variant }}">
  <a href="{{ $a->url() }}" class="card-link">
    @unless (in_array($variant, ['text', 'mini'], true))
      @include('partials.visual', ['a' => $a, 'size' => $variant === 'big' ? 'big' : ''])
    @endunless
    <div class="card-body-a">
      <div class="kicker"><span class="type">{{ $a->label() }}</span>@if ($a->verdict)<span class="verdict-chip">{{ $a->verdict['label'] }}</span>@endif<span class="sep">·</span><span>{{ $a->rubrique?->name }}</span></div>
      <h3 class="card-title">{{ $a->title }}</h3>
      @if (in_array($variant, ['big', 'row'], true))<p class="card-chapo">{{ $a->chapo }}</p>@endif
      <div class="meta">{{ $a->author?->name ?? 'La rédaction' }} · <time datetime="{{ $a->published_at?->toDateString() }}">{{ fr_date($a->published_at) }}</time> · {{ $a->read_time }} min</div>
    </div>
  </a>
</article>
