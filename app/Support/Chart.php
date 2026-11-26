<?php

namespace App\Support;

/** Graphiques SVG rendus côté serveur (aucune dépendance JavaScript externe). */
class Chart
{
    public const PALETTE = ['var(--red)', 'var(--ink)', 'var(--muted)', '#C88A12', '#1F7A4D', '#5B6EE1', '#B05FA8', 'var(--line)'];

    /** Courbe : $series = [['label' => 'Visites', 'values' => [...], 'class' => 'line'], …] */
    public static function line(array $labels, array $series, int $h = 260, bool $area = true): string
    {
        $w = 900; $pl = 44; $pr = 10; $pt = 10; $pb = 26;
        $max = max(1, ...array_map(fn ($s) => max([0, ...$s['values']]), $series));
        $max = self::nice($max);
        $n = max(1, count($labels) - 1);
        $x = fn ($i) => $pl + ($w - $pl - $pr) * $i / $n;
        $y = fn ($v) => $pt + ($h - $pt - $pb) * (1 - $v / $max);
        $svg = '<svg class="vx-chart" viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="none" role="img">';
        for ($g = 0; $g <= 4; $g++) {
            $v = $max * $g / 4; $yy = round($y($v), 1);
            $svg .= '<line class="grid" x1="'.$pl.'" x2="'.($w - $pr).'" y1="'.$yy.'" y2="'.$yy.'"/><text class="axis" x="'.($pl - 8).'" y="'.($yy + 4).'" text-anchor="end">'.self::short($v).'</text>';
        }
        $step = max(1, (int) ceil(count($labels) / 10));
        foreach ($labels as $i => $l) {
            if ($i % $step === 0) $svg .= '<text class="axis" x="'.round($x($i), 1).'" y="'.($h - 6).'" text-anchor="middle">'.e($l).'</text>';
        }
        foreach ($series as $k => $s) {
            $pts = []; foreach ($s['values'] as $i => $v) $pts[] = round($x($i), 1).','.round($y($v), 1);
            if ($k === 0 && $area && $pts) {
                $svg .= '<path class="area" d="M'.$pl.','.($h - $pb).' L'.implode(' L', $pts).' L'.round($x(count($pts) - 1), 1).','.($h - $pb).' Z"/>';
            }
            $svg .= '<polyline class="'.($s['class'] ?? 'line').'" points="'.implode(' ', $pts).'"/>';
        }
        // zones de survol (infobulle)
        $bw = ($w - $pl - $pr) / max(1, count($labels));
        foreach ($labels as $i => $l) {
            $tip = e($l).' · '.implode(' · ', array_map(fn ($s) => e($s['label']).' : '.nf($s['values'][$i] ?? 0), $series));
            $svg .= '<g class="pt" data-tip="'.$tip.'"><rect class="hit" x="'.round($x($i) - $bw / 2, 1).'" y="0" width="'.round($bw, 1).'" height="'.$h.'"/><circle class="dot-pt" cx="'.round($x($i), 1).'" cy="'.round($y($series[0]['values'][$i] ?? 0), 1).'" r="4"/></g>';
        }

        return $svg.'</svg>';
    }

    public static function bars(array $labels, array $values, int $h = 220, ?float $hot = null, bool $showAxis = true): string
    {
        $w = 900; $pb = $showAxis ? 24 : 4; $max = max(1, ...$values ?: [1]);
        $n = max(1, count($values)); $gap = 6; $bw = ($w - $gap * ($n - 1)) / $n;
        $svg = '<svg class="vx-chart" viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="none" role="img">';
        $step = max(1, (int) ceil($n / 12));
        foreach ($values as $i => $v) {
            $bh = ($h - $pb - 4) * $v / $max; $xx = $i * ($bw + $gap);
            $cls = $hot !== null && $v >= $hot ? 'bar hot' : 'bar';
            $svg .= '<g class="pt" data-tip="'.e($labels[$i] ?? '').' : '.nf($v).'"><rect class="'.$cls.'" rx="4" x="'.round($xx, 1).'" y="'.round($h - $pb - $bh, 1).'" width="'.round($bw, 1).'" height="'.round(max(0, $bh), 1).'"/></g>';
            if ($showAxis && $i % $step === 0) $svg .= '<text class="axis" x="'.round($xx + $bw / 2, 1).'" y="'.($h - 6).'" text-anchor="middle">'.e($labels[$i] ?? '').'</text>';
        }

        return $svg.'</svg>';
    }

    /** Anneau + légende : $items = [['k' => 'Mobile', 'n' => 71], …] */
    public static function donut(array $items): string
    {
        $total = max(1, array_sum(array_column($items, 'n')));
        $r = 15.915; $off = 25; $svg = '<div class="donut-wrap"><svg viewBox="0 0 42 42" class="vx-chart" role="img">';
        $svg .= '<circle cx="21" cy="21" r="'.$r.'" fill="none" stroke="var(--surface-2)" stroke-width="6"/>';
        $legend = '<ul>';
        foreach (array_values($items) as $i => $it) {
            $p = $it['n'] / $total * 100; $c = self::PALETTE[$i % count(self::PALETTE)];
            $svg .= '<g class="pt" data-tip="'.e($it['k']).' : '.round($p).' %"><circle cx="21" cy="21" r="'.$r.'" fill="none" stroke="'.$c.'" stroke-width="6" stroke-dasharray="'.round($p, 2).' '.round(100 - $p, 2).'" stroke-dashoffset="'.round($off, 2).'"/></g>';
            $off -= $p;
            $legend .= '<li><i style="background:'.$c.'"></i>'.e($it['k']).'<b>'.round($p).' %</b></li>';
        }

        return $svg.'</svg>'.$legend.'</ul></div>';
    }

    protected static function nice(float $v): float
    {
        $p = 10 ** floor(log10(max(1, $v)));
        foreach ([1, 2, 2.5, 5, 10] as $m) if ($v <= $m * $p) return $m * $p;

        return 10 * $p;
    }

    protected static function short(float $v): string
    {
        return $v >= 1000 ? nf($v / 1000, $v >= 10000 ? 0 : 1).' k' : nf($v);
    }
}
