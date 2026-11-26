<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\NewsletterDelivery;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrackingController extends Controller
{
    /** Balise de fin de lecture : durée active et profondeur de défilement. */
    public function collect(Request $r)
    {
        $id = (int) $r->input('pv');
        $d = min(3600, max(0, (int) $r->input('d')));
        $s = min(100, max(0, (int) $r->input('s')));
        if ($id > 0) {
            PageView::whereKey($id)->where('created_at', '>=', now()->subHours(6))
                ->update(['duration' => \DB::raw('CASE WHEN duration < '.$d.' THEN '.$d.' ELSE duration END'), 'scroll' => \DB::raw('CASE WHEN scroll < '.$s.' THEN '.$s.' ELSE scroll END')]);
        }

        return response()->noContent();
    }

    public function open(string $token)
    {
        $d = NewsletterDelivery::where('token', $token)->first();
        if ($d && ! $d->opened_at) {
            $d->update(['opened_at' => now()]);
            $d->newsletter()->increment('opens');
        }
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store, max-age=0']);
    }

    public function click(Request $r, string $token)
    {
        $url = (string) $r->query('u', route('home'));
        $base = rtrim(config('app.url'), '/');
        // Pas de redirection ouverte : uniquement vers le site lui-même
        if (! str_starts_with($url, $base) && ! str_starts_with($url, url('/'))) {
            $url = route('home');
        }
        $d = NewsletterDelivery::where('token', $token)->first();
        if ($d) {
            if (! $d->opened_at) {
                $d->opened_at = now();
                $d->newsletter()->increment('opens');
            }
            if (! $d->clicked_at) {
                $d->clicked_at = now();
                $d->newsletter()->increment('clicks');
            }
            $d->save();
        }

        return redirect()->away($url);
    }

    public function campaign(Request $r, Campaign $campaign)
    {
        $key = 'vox.adclick.'.$campaign->id.'.'.sha1($r->ip().$r->userAgent());
        if (Cache::add($key, 1, now()->addMinutes(30))) {
            $campaign->increment('clicks');
        }
        $target = $campaign->url && filter_var($campaign->url, FILTER_VALIDATE_URL) ? $campaign->url : route('annoncer');

        return redirect()->away($target);
    }
}
