@php($mostRead = \Illuminate\Support\Facades\Cache::remember('vox.mostread', 600, fn () => \App\Models\Article::published()->orderByDesc('views')->limit(5)->get(['id', 'slug', 'title'])))
<aside class="sidebar">
  <section class="side-box">
    <h2 class="side-title">Les plus lus</h2>
    <ol class="most">
      @foreach ($mostRead as $i => $a)
        <li><span class="rank">{{ $i + 1 }}</span><a href="{{ $a->url() }}">{{ $a->title }}</a></li>
      @endforeach
    </ol>
  </section>
  @include('partials.ad', ['pos' => 'pave'])
  <section class="side-box news-box">
    <h2 class="side-title">La lettre des talents</h2>
    <p>Un talent à découvrir chaque semaine, dans votre boîte mail.</p>
    @include('partials.nl-form', ['source' => 'Colonne', 'variant' => 'side', 'class' => 'd-grid gap-2'])
  </section>
</aside>
