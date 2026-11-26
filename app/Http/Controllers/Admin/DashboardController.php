<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Lead;
use App\Models\Publication;
use App\Models\Submission;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Analytics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Analytics $an)
    {
        $s = $an->summary(30);
        $daily = $an->daily(30);
        $campaigns = Campaign::orderByDesc('impressions')->get();
        $running = Campaign::running()->get();

        return view('admin.dashboard', [
            's' => $s, 'daily' => $daily, 'sources' => $an->breakdown('source', 30),
            'byRub' => $an->byRubrique(30), 'campaigns' => $campaigns, 'realtime' => $an->realtime(),
            'published' => Article::published()->count(), 'drafts' => Article::where('status', 'brouillon')->count(),
            'subscribers' => Subscriber::active()->count(), 'pendingSubs' => Submission::where('status', 'Nouvelle')->count(),
            'revenue' => $running->sum('budget'),
            'ctr' => $running->sum('impressions') ? round($running->sum('clicks') / $running->sum('impressions') * 100, 2) : 0,
            'top' => Article::published()->with(['rubrique', 'author'])->orderByDesc('views')->limit(6)->get(),
            'upcoming' => Article::with('author')->where(fn ($q) => $q->where('status', 'programmé')->where('published_at', '>', now())->orWhere(fn ($w) => $w->where('status', 'brouillon')))->orderBy('published_at')->limit(5)->get(),
            'pendingComments' => Comment::where('status', 'en attente')->count(),
            'unread' => Lead::whereNull('read_at')->count(),
            'activity' => ActivityLog::latest('created_at')->limit(5)->get(),
            'aiBots' => array_sum(array_column($an->bots(30, 'ai'), 'n')),
        ]);
    }

    public function stats(Request $r, Analytics $an)
    {
        $p = in_array((int) $r->query('periode'), [7, 30, 90, 365], true) ? (int) $r->query('periode') : 30;

        return view('admin.stats', [
            'p' => $p, 's' => $an->summary($p), 'daily' => $an->daily($p),
            'devices' => $an->breakdown('device', $p), 'sources' => $an->breakdown('source', $p),
            'countries' => $an->breakdown('country', $p, 10), 'hours' => $an->hours($p),
            'top' => $an->topArticles($p, 10), 'searches' => $an->topSearches($p, 10), 'referrers' => $an->referrers($p, 10),
            'bots' => $an->bots($p), 'byRub' => $an->byRubrique($p), 'realtime' => $an->realtime(),
        ]);
    }

    /** Export CSV des statistiques quotidiennes. */
    public function export(Request $r, Analytics $an)
    {
        $p = in_array((int) $r->query('periode'), [7, 30, 90, 365], true) ? (int) $r->query('periode') : 30;
        $rows = $an->daily($p);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Visites', 'Visiteurs uniques', 'Pages vues'], ';');
            foreach ($rows as $row) fputcsv($out, [$row['date'], $row['v'], $row['u'], $row['p']], ';');
            fclose($out);
        }, 'statistiques-'.$p.'j-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function journal()
    {
        return view('admin.journal', ['logs' => ActivityLog::latest('created_at')->paginate(40)]);
    }

    public function search(Request $r)
    {
        $q = trim((string) $r->query('q'));
        if (mb_strlen($q) < 2) return response()->json([]);
        $like = '%'.$q.'%';
        $hits = [];
        foreach (Article::where('title', 'like', $like)->limit(5)->get() as $a) $hits[] = ['k' => 'Article', 't' => $a->title, 'href' => route('admin.articles.edit', $a->id)];
        if ($r->user()->canDo('users')) foreach (User::where('name', 'like', $like)->orWhere('email', 'like', $like)->limit(3)->get() as $u) $hits[] = ['k' => 'Utilisateur', 't' => $u->name, 'href' => route('admin.users')];
        if ($r->user()->canDo('ads')) foreach (Campaign::where('advertiser', 'like', $like)->limit(3)->get() as $c) $hits[] = ['k' => 'Campagne', 't' => $c->advertiser, 'href' => route('admin.ads')];
        if ($r->user()->canDo('library')) foreach (Publication::where('title', 'like', $like)->limit(3)->get() as $p) $hits[] = ['k' => 'Publication', 't' => $p->title, 'href' => route('admin.library')];
        if ($r->user()->canDo('newsletter')) foreach (Subscriber::where('email', 'like', $like)->limit(3)->get() as $s) $hits[] = ['k' => 'Abonné', 't' => $s->email, 'href' => route('admin.subscribers', ['q' => $s->email])];

        return response()->json($hits);
    }
}
