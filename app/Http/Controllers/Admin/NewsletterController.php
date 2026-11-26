<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletter;
use App\Mail\NewsletterIssue;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Newsletter;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NewsletterController extends Controller
{
    public function index(Request $r)
    {
        $sent = Newsletter::where('status', 'Envoyée')->get();

        return view('admin.newsletter', [
            'newsletters' => Newsletter::latest()->get(),
            'articles' => Article::published()->with('rubrique')->latestFirst()->limit(20)->get(),
            'subscribers' => Subscriber::active()->count(),
            'rate' => $sent->count() ? round($sent->avg(fn ($n) => $n->openRate()), 1) : 0,
            'compose' => $r->boolean('nouvelle') || $r->session()->has('errors'),
        ]);
    }

    public function send(Request $r)
    {
        $d = $r->validate(['subject' => 'required|string|max:150', 'intro' => 'nullable|string|max:1500', 'articles' => 'required|array|min:1', 'articles.*' => 'integer|exists:articles,id'],
            ['subject.required' => 'Indiquez un objet.', 'articles.required' => 'Choisissez au moins un article.']);
        $nl = new Newsletter(['subject' => $d['subject'], 'intro' => $d['intro'] ?? '', 'article_ids' => array_values($d['articles']), 'status' => 'Brouillon']);
        if ($r->input('mode') === 'test') {
            $nl->sent_at = now();
            try {
                Mail::to($r->user()->email)->send(new NewsletterIssue($nl, null, null, true));
            } catch (Throwable $e) {
                return back()->withInput()->with('err', 'Échec du test : '.$e->getMessage());
            }

            return back()->withInput()->with('ok', 'Test envoyé à '.$r->user()->email.'.')->with('compose', true);
        }
        $count = Subscriber::active()->count();
        if (! $count) return back()->withInput()->with('err', 'Aucun abonné confirmé.');
        $nl->status = 'Programmée';
        $nl->recipients = $count;
        $nl->save();
        SendNewsletter::dispatch($nl);
        ActivityLog::record('a envoyé la newsletter « '.$nl->subject.' » à '.$count.' abonné(s)');

        return redirect()->route('admin.newsletter')->with('ok', 'Newsletter en cours d’envoi à '.$count.' abonné(s).');
    }

    public function subscribers(Request $r)
    {
        $q = Subscriber::when($r->query('q'), fn ($w, $s) => $w->where('email', 'like', "%$s%"))
            ->when($r->query('etat') === 'attente', fn ($w) => $w->whereNull('confirmed_at'))
            ->when($r->query('etat') === 'desinscrits', fn ($w) => $w->whereNotNull('unsubscribed_at'))
            ->when(! $r->query('etat'), fn ($w) => $w->active())->latest();

        return view('admin.subscribers', ['subs' => $q->paginate(50)->withQueryString(), 'counts' => [
            'actifs' => Subscriber::active()->count(), 'attente' => Subscriber::whereNull('confirmed_at')->count(), 'desinscrits' => Subscriber::whereNotNull('unsubscribed_at')->count()]]);
    }

    public function addSubscriber(Request $r)
    {
        $d = $r->validate(['email' => 'required|email:rfc|unique:subscribers,email'], ['email.unique' => 'Cette adresse est déjà inscrite.']);
        Subscriber::create(['email' => mb_strtolower($d['email']), 'source' => 'Ajout manuel', 'confirmed_at' => now()]);
        ActivityLog::record('a ajouté l’abonné '.$d['email']);

        return back()->with('ok', 'Abonné ajouté.');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['E-mail', 'Source', 'Inscription', 'Confirmé le'], ';');
            Subscriber::active()->orderBy('email')->chunk(500, function ($c) use ($out) {
                foreach ($c as $s) fputcsv($out, [$s->email, $s->source, $s->created_at?->format('Y-m-d'), $s->confirmed_at?->format('Y-m-d')], ';');
            });
            fclose($out);
        }, 'abonnes-newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroySubscriber(Subscriber $subscriber)
    {
        $subscriber->delete();
        ActivityLog::record('a retiré l’abonné '.$subscriber->email);

        return back()->with('ok', 'Abonné retiré.');
    }
}
