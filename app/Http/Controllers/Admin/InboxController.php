<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VoxMail;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\Submission;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InboxController extends Controller
{
    public function index(Request $r)
    {
        $tab = $r->query('onglet') === 'messages' ? 'messages' : 'talents';
        $user = $r->user();
        $leads = Lead::when(! $user->canDo('ads'), fn ($q) => $q->where('kind', '!=', 'Annonceur'))->latest()->paginate(30, ['*'], 'p')->withQueryString();

        return view('admin.inbox', ['tab' => $tab, 'subs' => Submission::latest()->paginate(30)->withQueryString(), 'leads' => $leads,
            'subsCount' => Submission::count(), 'leadsCount' => Lead::count(), 'unread' => Lead::whereNull('read_at')->count()]);
    }

    public function status(Request $r, Submission $submission)
    {
        $d = $r->validate(['status' => ['required', Rule::in(Submission::STATUSES)]]);
        $old = $submission->status;
        $submission->update($d);
        ActivityLog::record('a passé la proposition « '.$submission->project.' » en « '.$d['status'].' »');
        if ($old !== $d['status'] && in_array($d['status'], ['Retenue', 'Refusée', 'En étude'], true) && $r->boolean('notify', true)) {
            $lines = match ($d['status']) {
                'Retenue' => ['Bonne nouvelle : votre proposition « **'.e($submission->project).'** » a été **retenue** par la rédaction !', 'Un journaliste va vous contacter très prochainement pour préparer le sujet.'],
                'Refusée' => ['Merci d’avoir proposé « **'.e($submission->project).'** ». Après examen, la rédaction ne pourra pas donner suite pour le moment.', 'Cette décision ne préjuge pas de la qualité du projet : n’hésitez pas à revenir vers nous avec de nouveaux éléments.'],
                default => ['Votre proposition « **'.e($submission->project).'** » est en cours d’étude par la rédaction. Nous vérifions les informations transmises.'],
            };
            Notifier::send($submission->email, new VoxMail('Votre proposition : '.$d['status'], 'Suivi de votre proposition', array_merge(['Bonjour '.e($submission->name).','], $lines)));
        }

        return back()->with('ok', 'Statut mis à jour'.($old !== $d['status'] ? ' — le proposant a été prévenu par e-mail.' : '.'));
    }

    public function read(Lead $lead)
    {
        $lead->update(['read_at' => $lead->read_at ? null : now()]);

        return back()->with('ok', $lead->read_at ? 'Message marqué comme lu.' : 'Message marqué comme non lu.');
    }

    public function destroyLead(Lead $lead)
    {
        $lead->delete();
        ActivityLog::record('a supprimé un message de '.$lead->name);

        return back()->with('ok', 'Message supprimé.');
    }

    public function destroySubmission(Submission $submission)
    {
        $submission->delete();
        ActivityLog::record('a supprimé la proposition « '.$submission->project.' »');

        return back()->with('ok', 'Proposition supprimée.');
    }
}
