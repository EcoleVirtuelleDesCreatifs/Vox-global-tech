<?php

use App\Support\Settings;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    /** Lecture d'un réglage du média (mis en cache). */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $parts = preg_split('/[\s-]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(implode('', array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2))));
    }
}

if (! function_exists('fr_date')) {
    function fr_date(mixed $date, bool $short = false): string
    {
        if (! $date) {
            return '';
        }
        $d = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $d->locale('fr');

        return $short ? $d->translatedFormat('j M') : $d->translatedFormat('j F Y');
    }
}

if (! function_exists('nf')) {
    function nf(int|float|null $n, int $decimals = 0): string
    {
        return number_format((float) $n, $decimals, ',', "\u{00A0}");
    }
}

if (! function_exists('fcfa')) {
    function fcfa(int|float|null $n): string
    {
        return $n ? nf($n).' FCFA' : 'Gratuit';
    }
}

if (! function_exists('ko')) {
    function ko(int|float|null $kb): string
    {
        return $kb >= 1024 ? nf($kb / 1024, 1).' Mo' : nf($kb).' Ko';
    }
}

if (! function_exists('svg_icon')) {
    function svg_icon(string $id, string $class = 'svg-i'): string
    {
        return '<svg class="'.e($class).'" aria-hidden="true"><use href="#i-'.e($id).'"/></svg>';
    }
}
