<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** Notifie instantanément Bing, Yandex, Seznam, Naver (protocole IndexNow) d'une URL publiée ou modifiée. */
class IndexNow
{
    public static function key(): string
    {
        $key = setting('indexnow_key');
        if (! $key) {
            $key = Str::lower(Str::random(32));
            app(\App\Support\Settings::class)->set(['indexnow_key' => $key]);
        }

        return $key;
    }

    public function submit(array $urls): bool
    {
        if (! setting('indexnow') || app()->environment(['local', 'testing']) || ! $urls) {
            return false;
        }
        try {
            $host = parse_url(config('app.url'), PHP_URL_HOST);
            $res = Http::timeout(6)->asJson()->post('https://api.indexnow.org/indexnow', [
                'host' => $host,
                'key' => self::key(),
                'keyLocation' => url('/'.self::key().'.txt'),
                'urlList' => array_values($urls),
            ]);

            return $res->successful();
        } catch (Throwable $e) {
            Log::warning('IndexNow : '.$e->getMessage());

            return false;
        }
    }
}
