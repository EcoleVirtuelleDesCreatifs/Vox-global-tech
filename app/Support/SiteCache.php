<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/** Invalidation des caches publics après une modification éditoriale. */
class SiteCache
{
    public static function flush(): void
    {
        foreach (['vox.home', 'vox.nav', 'vox.mostread', 'vox.mega', 'vox.ads', 'vox.slots', 'vox.llms', 'vox.llms.full',
            'vox.sitemap.pages', 'vox.sitemap.articles', 'vox.sitemap.news', 'vox.sitemap.taxonomies', 'vox.sitemap.publications'] as $k) {
            Cache::forget($k);
        }
    }
}
