<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** Téléversement dans la médiathèque : redimensionnement (2000 px max), conversion WebP, dimensions. */
class MediaUploader
{
    public function store(UploadedFile $file, ?User $user = null, ?string $alt = null): Media
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $base = Str::limit(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), 60, '') ?: 'fichier';
        $dir = 'medias/'.now()->format('Y/m');
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/') && $ext !== 'svg';
        $w = $h = 0;

        if ($isImage && function_exists('imagecreatefromstring') && ($src = @imagecreatefromstring(file_get_contents($file->getRealPath())))) {
            [$w, $h] = [imagesx($src), imagesy($src)];
            $max = 2000;
            if ($w > $max) {
                $nh = (int) round($h * $max / $w);
                $dst = imagecreatetruecolor($max, $nh);
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $max, $nh, $w, $h);
                imagedestroy($src); $src = $dst; [$w, $h] = [$max, $nh];
            }
            $name = $base.'-'.Str::lower(Str::random(5)).(function_exists('imagewebp') ? '.webp' : '.jpg');
            $path = $dir.'/'.$name;
            \Storage::disk('public')->makeDirectory($dir);
            $full = \Storage::disk('public')->path($path);
            function_exists('imagewebp') ? imagewebp($src, $full, 82) : imagejpeg($src, $full, 84);
            imagedestroy($src);
            $mime = function_exists('imagewebp') ? 'image/webp' : 'image/jpeg';
        } else {
            $name = $base.'-'.Str::lower(Str::random(5)).'.'.$ext;
            $path = $file->storeAs($dir, $name, 'public');
            $mime = $file->getMimeType();
        }

        return Media::create([
            'name' => $name, 'path' => $path, 'type' => $isImage || $ext === 'svg' ? 'Image' : 'Document', 'mime' => $mime,
            'size' => (int) ceil(\Storage::disk('public')->size($path) / 1024), 'width' => $w, 'height' => $h,
            'alt' => $alt, 'tone' => random_int(0, 3), 'user_id' => $user?->id,
        ]);
    }
}
