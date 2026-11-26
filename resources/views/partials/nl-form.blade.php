{{-- Formulaire d'inscription newsletter réutilisable : $source, $variant (home|side|footer|page) --}}
@php($mine = session('nl_source') === $source)
@if ($mine && session('nl_ok'))
  <p class="ok-t {{ $variant === 'home' ? 'nl-note' : '' }}" role="status">{{ session('nl_ok') }}</p>
@else
<form method="POST" action="{{ route('newsletter.subscribe') }}" class="{{ $class ?? '' }}" novalidate>
  @csrf
  <input type="hidden" name="source" value="{{ $source }}">
  <input type="hidden" name="_t" value="{{ time() }}">
  <div class="hp" aria-hidden="true"><label>Site web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  {{ $slot ?? '' }}
  @switch($variant)
    @case('home')
      <label for="nl-h" class="visually-hidden">Votre e-mail</label>
      <input id="nl-h" type="email" name="email" value="{{ $mine ? old('email') : '' }}" placeholder="vous@exemple.com" class="form-control @if($mine && $errors->newsletter->any()) is-bad @endif" required autocomplete="email">
      <button class="btn btn-red" type="submit">S’abonner</button>
      @if ($mine && $errors->newsletter->any())<p class="field-err nl-note">{{ $errors->newsletter->first('email') }}</p>@else<p class="nl-note">Gratuit, sans publicité intrusive, désinscription en un clic.</p>@endif
      @break
    @case('side')
      <label class="visually-hidden" for="nl-side">Votre e-mail</label>
      <input id="nl-side" class="form-control @if($mine && $errors->newsletter->any()) is-bad @endif" type="email" name="email" placeholder="vous@exemple.com" required autocomplete="email">
      @if ($mine && $errors->newsletter->any())<div class="field-err">{{ $errors->newsletter->first('email') }}</div>@endif
      <button class="btn btn-red" type="submit">S’inscrire</button>
      @break
    @case('footer')
      <label for="nl-f" class="f-news-l">Newsletter VOX · chaque semaine</label>
      <div class="news"><input id="nl-f" class="form-control" type="email" name="email" placeholder="vous@exemple.com" required autocomplete="email"><button class="btn btn-red" type="submit">S’abonner</button></div>
      @if ($mine && $errors->newsletter->any())<div class="field-err">{{ $errors->newsletter->first('email') }}</div>@endif
      @break
    @default
      <label class="form-label" for="nl-p">Votre adresse e-mail</label>
      <input id="nl-p" type="email" name="email" class="form-control @if($mine && $errors->newsletter->any()) is-bad @endif" value="{{ $mine ? old('email') : '' }}" placeholder="vous@exemple.com" required autocomplete="email">
      @if ($mine && $errors->newsletter->any())<div class="field-err">{{ $errors->newsletter->first('email') }}</div>@endif
      <button class="btn btn-red justify-self-start" type="submit">S’abonner à la Newsletter VOX</button>
      <p class="note mb-0">Double confirmation par e-mail. Désinscription possible à tout moment depuis chaque envoi.</p>
  @endswitch
</form>
@endif
