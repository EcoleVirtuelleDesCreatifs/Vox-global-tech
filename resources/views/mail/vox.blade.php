<x-mail::message>
# {{ $heading }}

@foreach ($lines as $line)
{!! $line !!}

@endforeach
@if ($table)
<x-mail::table>
| | |
|:--|:--|
@foreach ($table as $row)
| **{{ $row[0] }}** | {{ str_replace(['|', "\n"], ['/', ' '], (string) $row[1]) }} |
@endforeach
</x-mail::table>
@endif
@if ($button)
<x-mail::button :url="$button['url']" color="primary">
{{ $button['label'] }}
</x-mail::button>
@endif
@foreach ($outro as $line)
{!! $line !!}

@endforeach
La rédaction de {{ setting('name') }}
</x-mail::message>
