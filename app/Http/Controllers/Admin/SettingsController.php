<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\DemoData;
use App\Services\IndexNow;
use App\Support\Settings;
use App\Support\SiteCache;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public const TABS = ['general' => 'Général', 'seo' => 'Référencement & GEO', 'social' => 'Réseaux sociaux', 'moder' => 'Commentaires et publicité', 'mail' => 'Notifications', 'data' => 'Données'];

    public function index(Request $r)
    {
        $tab = array_key_exists($r->query('onglet'), self::TABS) ? $r->query('onglet') : 'general';

        return view('admin.settings', ['tab' => $tab, 'tabs' => self::TABS, 's' => app(Settings::class)->all(), 'indexnowKey' => IndexNow::key()]);
    }

    public function update(Request $r, Settings $settings)
    {
        $tab = $r->input('tab', 'general');
        $rules = match ($tab) {
            'general' => ['name' => 'required|string|max:80', 'tagline' => 'required|string|max:200', 'per_page' => 'required|integer|in:4,6,9,12',
                'address' => 'nullable|string|max:200', 'phone' => 'nullable|string|max:40', 'email_redaction' => 'required|email', 'email_partners' => 'required|email'],
            'seo' => ['seo_title' => 'required|string|max:80', 'seo_description' => 'required|string|max:200', 'google_verification' => 'nullable|string|max:120', 'bing_verification' => 'nullable|string|max:120'],
            'social' => ['social' => 'array', 'social.*' => 'nullable|url|max:250'],
            default => [],
        };
        $data = $r->validate($rules, ['url' => 'Adresse invalide (https://…).']);
        $bools = ['general' => ['maintenance'], 'seo' => ['ai_crawlers', 'indexnow'], 'moder' => ['comments_on', 'moderate', 'ads_on'], 'mail' => ['notify_comments', 'notify_forms', 'weekly_report']][$tab] ?? [];
        foreach ($bools as $b) $data[$b] = $r->boolean($b);
        if ($tab === 'social') $data['social'] = array_merge(Settings::DEFAULTS['social'], array_map(fn ($v) => (string) $v, $data['social'] ?? []));
        $settings->set($data);
        SiteCache::flush();
        ActivityLog::record('a modifié les réglages ('.(self::TABS[$tab] ?? $tab).')');

        return redirect()->route('admin.settings', ['onglet' => $tab])->with('ok', 'Réglages enregistrés.');
    }

    public function purgeDemo(Request $r, DemoData $demo)
    {
        if ($r->input('do') === 'regen') {
            $demo->purge();
            $n = $demo->analytics(90);
            ActivityLog::record('a régénéré les statistiques de démonstration');

            return back()->with('ok', nf($n).' pages vues de démonstration générées.');
        }
        $demo->purge();
        ActivityLog::record('a remis les statistiques à zéro');

        return back()->with('ok', 'Statistiques remises à zéro : la mesure repart des vraies visites.');
    }
}
