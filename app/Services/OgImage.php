<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;

/** Génère l'image de partage social (1200×630, PNG) d'un article, aux couleurs de la charte. */
class OgImage
{
    protected const W = 1200;
    protected const H = 630;

    public function forArticle(Article $a): string
    {
        $path = 'og/'.$a->id.'-'.substr(md5($a->title.$a->updated_at.$a->rubrique?->name), 0, 10).'.png';
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            $disk->put($path, $this->render($a->title, mb_strtoupper(($a->rubrique?->name ?? 'VOX TECH GLOBAL').' · '.$a->label()), $a->author?->name, $a->hue()));
        }

        return $disk->path($path);
    }

    public function render(string $title, string $kicker, ?string $byline = null, int $hue = 0): string
    {
        $im = imagecreatetruecolor(self::W, self::H);
        $bgs = [[17, 18, 20], [28, 29, 34], [24, 20, 22], [14, 15, 17]];
        [$r, $g, $b] = $bgs[$hue % 4];
        imagefill($im, 0, 0, imagecolorallocate($im, $r, $g, $b));
        $red = imagecolorallocate($im, 211, 20, 27);
        $white = imagecolorallocate($im, 246, 245, 242);
        $muted = imagecolorallocate($im, 163, 163, 169);
        $ring = imagecolorallocate($im, (int) ($r + 50), (int) ($g + 10), (int) ($b + 12));
        imagesetthickness($im, 1);
        for ($t = 0; $t < 18; $t++) {
            imageellipse($im, 1010, 150, 540 + $t, 540 + $t, $ring);
        }
        $a = deg2rad(58);
        imagefilledellipse($im, (int) (1010 + 279 * cos($a)), (int) (150 + 279 * sin($a)), 58, 58, $red);
        imagefilledrectangle($im, 80, 96, 150, 104, $red);

        $font = resource_path('fonts/Outfit-SemiBold.ttf');
        $fontR = resource_path('fonts/Outfit-Regular.ttf');
        if (function_exists('imagettftext') && is_file($font)) {
            imagettftext($im, 20, 0, 80, 150, $muted, $fontR, $kicker);
            $size = mb_strlen($title) > 90 ? 44 : (mb_strlen($title) > 60 ? 52 : 60);
            $lines = $this->wrap($title, $font, $size, 980);
            $y = 240;
            foreach (array_slice($lines, 0, 4) as $line) {
                imagettftext($im, $size, 0, 80, $y, $white, $font, $line);
                $y += (int) ($size * 1.28);
            }
            $brand = imagettftext($im, 26, 0, 80, 570, $white, $font, 'VOX TECH GLOBAL');
            if ($byline) {
                imagettftext($im, 20, 0, $brand[2] + 28, 568, $muted, $fontR, '· Par '.$byline);
            }
        } else {
            imagestring($im, 5, 80, 200, mb_substr($title, 0, 90), $white);
        }
        ob_start();
        imagepng($im, null, 7);
        $png = ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    protected function wrap(string $text, string $font, int $size, int $max): array
    {
        $words = preg_split('/\s+/u', $text);
        $lines = [];
        $line = '';
        foreach ($words as $w) {
            $try = trim($line.' '.$w);
            $box = imagettfbbox($size, 0, $font, $try);
            if ($box[2] - $box[0] > $max && $line !== '') {
                $lines[] = $line;
                $line = $w;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        if (count($lines) > 4) {
            $lines = array_slice($lines, 0, 4);
            $lines[3] = rtrim($lines[3], ' ,.;:').'…';
        }

        return $lines;
    }
}
