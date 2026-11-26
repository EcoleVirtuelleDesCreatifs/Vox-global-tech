<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdSlot;
use App\Models\Campaign;
use App\Support\SiteCache;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index()
    {
        $slots = AdSlot::orderBy('position')->get();
        $campaigns = Campaign::latest()->get();
        $revenue = $slots->map(fn ($s) => ['s' => $s, 'v' => $campaigns->where('slot', $s->code)->sum('budget')]);

        return view('admin.ads', ['slots' => $slots, 'campaigns' => $campaigns, 'revenue' => $revenue, 'running' => Campaign::running()->get()->keyBy('slot')]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['advertiser' => 'required|string|max:120', 'slot' => 'required|exists:ad_slots,code', 'title' => 'required|string|max:160',
            'cta' => 'nullable|string|max:40', 'url' => 'nullable|url|max:250', 'budget' => 'nullable|integer|min:0',
            'starts_on' => 'required|date', 'ends_on' => 'required|date|after_or_equal:starts_on'],
            ['ends_on.after_or_equal' => 'La fin doit suivre le début.', 'url.url' => 'Adresse de destination invalide (https://…).']);
        // Une seule campagne active par emplacement
        Campaign::where('slot', $d['slot'])->where('active', true)->update(['active' => false]);
        $c = Campaign::create(array_merge($d, ['cta' => ($d['cta'] ?? null) ?: 'Découvrir', 'budget' => $d['budget'] ?? 0, 'active' => true]));
        SiteCache::flush();
        ActivityLog::record('a activé la campagne '.$c->advertiser.' ('.$c->adSlot?->name.')');

        return back()->with('ok', 'Campagne activée.');
    }

    public function toggle(Campaign $campaign)
    {
        if (! $campaign->active) Campaign::where('slot', $campaign->slot)->where('active', true)->update(['active' => false]);
        $campaign->update(['active' => ! $campaign->active]);
        SiteCache::flush();
        ActivityLog::record(($campaign->active ? 'a activé' : 'a mis en pause').' la campagne '.$campaign->advertiser);

        return back()->with('ok', $campaign->active ? 'Campagne activée (les autres campagnes de cet emplacement sont en pause).' : 'Campagne en pause.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        SiteCache::flush();
        ActivityLog::record('a supprimé la campagne '.$campaign->advertiser);

        return back()->with('ok', 'Campagne supprimée.');
    }
}
