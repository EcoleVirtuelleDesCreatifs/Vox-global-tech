<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Réglages éditables depuis l'admin, avec valeurs par défaut et cache. */
class Settings
{
    public const DEFAULTS = [
        'name' => 'VOX TECH GLOBAL',
        'tagline' => 'La voix des talents, des innovations, des technologies et des actualités des small business émergents.',
        'per_page' => 6,
        'maintenance' => false,
        'seo_title' => 'VOX TECH GLOBAL, la voix des talents qui innovent',
        'seo_description' => 'Le média qui découvre et fait rayonner les talents, startups et innovations technologiques du monde, avec une attention particulière à l’Afrique.',
        'social' => ['facebook' => '', 'instagram' => '', 'linkedin' => '', 'tiktok' => '', 'youtube' => '', 'x' => ''],
        'comments_on' => true,
        'moderate' => true,
        'ads_on' => true,
        'ai_crawlers' => true,       // GEO : autoriser GPTBot, ClaudeBot, PerplexityBot…
        'indexnow' => true,          // ping Bing/Yandex/IndexNow à la publication
        'indexnow_key' => null,
        'google_verification' => '',
        'bing_verification' => '',
        'email_redaction' => 'redaction@voxtechglobal.com',
        'email_partners' => 'partenariats@voxtechglobal.com',
        'phone' => '',
        'address' => 'Abidjan, Côte d’Ivoire',
        'notify_comments' => true,
        'notify_forms' => true,
        'weekly_report' => true,
    ];

    protected ?array $cache = null;

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $stored = [];
        try {
            $stored = Cache::rememberForever('vox.settings', function () {
                return Schema::hasTable('settings') ? Setting::pluck('value', 'key')->all() : [];
            });
        } catch (Throwable) {
            $stored = [];
        }

        return $this->cache = array_replace(self::DEFAULTS, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        if (str_contains($key, '.')) {
            return data_get($all, $key, $default);
        }

        return $all[$key] ?? $default;
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget('vox.settings');
        $this->cache = null;
    }
}
