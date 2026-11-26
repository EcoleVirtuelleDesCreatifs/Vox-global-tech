{{ setting('name') }} — Newsletter VOX

{{ $newsletter->subject }}

{{ $newsletter->intro }}

@foreach ($articles as $a)
— {{ $a->title }}
{{ $a->chapo }}
{{ $a->url() }}

@endforeach
Se désabonner : {{ $unsubscribe }}
