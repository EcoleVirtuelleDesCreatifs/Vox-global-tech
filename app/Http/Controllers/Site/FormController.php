<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\VoxMail;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Lead;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Submission;
use App\Models\Subscriber;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class FormController extends Controller
{
    protected const MSG = [
        'required' => 'Ce champ est obligatoire.',
        'email' => 'Adresse e-mail invalide.',
        'max' => 'Texte trop long (:max caractères maximum).',
        'min' => 'Texte trop court (:min caractères minimum).',
        'accepted' => 'Merci de cocher cette case pour continuer.',
        'in' => 'Choix invalide.',
    ];

    /** Pot de miel anti-robots : le champ « website » doit rester vide. */
    protected function isSpam(Request $r): bool
    {
        return filled($r->input('website')) || ($r->filled('_t') && (time() - (int) $r->input('_t')) < 2);
    }

    public function comment(Request $r, Article $article)
    {
        abort_unless($article->isLive() && setting('comments_on'), 404);
        $data = $r->validateWithBag('comment', [
            'name' => 'required|string|max:80',
            'email' => 'required|email:rfc|max:160',
            'text' => 'required|string|min:3|max:2000',
        ], self::MSG);
        if ($this->isSpam($r)) {
            return back()->with('comment_sent', true);
        }
        $spammy = preg_match('/(https?:\/\/.*){3,}|gagnez de l.argent|casino|viagra|crypto.?gains?/iu', $data['text']);
        $comment = $article->comments()->create($data + [
            'status' => $spammy ? 'spam' : (setting('moderate') ? 'en attente' : 'approuvé'),
            'ip' => hash('sha256', $r->ip().config('app.key')),
        ]);
        if (setting('notify_comments') && $comment->status === 'en attente') {
            Notifier::send(Notifier::staff('email_redaction', ['Administrateur', 'Rédacteur en chef']), new VoxMail(
                'Nouveau commentaire à modérer · '.$article->title,
                'Un commentaire attend votre relecture',
                ['**'.e($comment->name).'** a commenté « '.e($article->title).' » :', '> '.e($comment->text)],
                ['label' => 'Modérer les commentaires', 'url' => route('admin.comments')],
            ));
        }

        return redirect()->to($article->url().'#commentaires')->with('comment_sent', $comment->status === 'approuvé' ? 'published' : 'pending');
    }

    public function subscribe(Request $r)
    {
        $source = mb_substr((string) $r->input('source', 'Site'), 0, 40);
        $data = $r->validateWithBag('newsletter', ['email' => 'required|email:rfc|max:160'], self::MSG);
        $r->session()->flash('nl_source', $source);
        if ($this->isSpam($r)) {
            return back()->with('nl_ok', 'Merci ! Vérifiez votre boîte mail pour confirmer votre inscription.');
        }
        $email = mb_strtolower(trim($data['email']));
        $sub = Subscriber::firstOrNew(['email' => $email]);
        if ($sub->exists && $sub->isActive()) {
            return back()->with('nl_ok', 'Vous êtes déjà inscrit·e à la Newsletter VOX. Merci de votre fidélité !');
        }
        $sub->fill(['source' => $sub->source ?: $source, 'unsubscribed_at' => null])->save();
        Notifier::send($sub->email, new VoxMail(
            'Confirmez votre inscription à la Newsletter VOX',
            'Plus qu’un clic pour recevoir la Newsletter VOX',
            ['Merci de votre intérêt pour '.setting('name').' ! Chaque semaine : le talent de la semaine, les innovations repérées par la rédaction, un décryptage et les opportunités Campus & Carrières.',
                'Pour finaliser votre inscription, confirmez votre adresse :'],
            ['label' => 'Je confirme mon inscription', 'url' => route('newsletter.confirm', $sub->token)],
            ['Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.'],
        ));

        return back()->with('nl_ok', 'Merci ! Un e-mail de confirmation vient de vous être envoyé à '.$sub->email.'.');
    }

    public function confirm(string $token)
    {
        $sub = Subscriber::where('token', $token)->firstOrFail();
        $first = ! $sub->confirmed_at;
        $sub->update(['confirmed_at' => $sub->confirmed_at ?? now(), 'unsubscribed_at' => null]);
        if ($first) {
            Notifier::send($sub->email, new VoxMail(
                'Bienvenue dans la Newsletter VOX',
                'Inscription confirmée, bienvenue !',
                ['Vous recevrez la Newsletter VOX chaque lundi matin : talents, innovations, décryptages et opportunités.',
                    'En attendant, découvrez nos derniers articles et dossiers spéciaux.'],
                ['label' => 'Lire '.setting('name'), 'url' => route('home')],
                ['Vous pourrez vous désabonner en un clic depuis chaque envoi.'],
            ));
        }

        return redirect()->route('newsletter')->with('nl_page', 'confirmed');
    }

    public function unsubscribe(Request $r, string $token)
    {
        $sub = Subscriber::where('token', $token)->firstOrFail();
        if ($r->isMethod('post') || $r->query('confirm')) {
            $sub->update(['unsubscribed_at' => now()]);
            ActivityLog::record('désinscription newsletter : '.$sub->email, null);
            if ($r->isMethod('post') && ($r->input('List-Unsubscribe') === 'One-Click' || ! $r->hasSession())) {
                return response('Désinscription effectuée.');
            }

            return redirect()->route('newsletter')->with('nl_page', 'unsubscribed');
        }

        return view('site.unsubscribe', compact('sub'));
    }

    public function proposer(Request $r)
    {
        $data = $r->validateWithBag('talent', [
            'kind' => ['required', Rule::in(Submission::KINDS)],
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc|max:160',
            'project' => 'required|string|max:160',
            'country' => 'nullable|string|max:80',
            'category' => ['required', Rule::in(Rubrique::pluck('name')->all())],
            'description' => 'required|string|min:30|max:800',
            'ok' => 'accepted',
        ], self::MSG + ['description.min' => 'Présentez le projet en au moins 30 caractères.', 'category.required' => 'Choisissez une rubrique.']);
        if (! $this->isSpam($r)) {
            unset($data['ok']);
            $s = Submission::create($data);
            if (setting('notify_forms')) {
                Notifier::send(Notifier::staff(), new VoxMail('Nouvelle proposition : '.$s->project, 'Nouvelle proposition de talent', [
                    e($s->name).' propose **'.e($s->kind).'** à la rédaction.', '> '.e($s->description)],
                    ['label' => 'Ouvrir la boîte de réception', 'url' => route('admin.inbox')], [],
                    [['Projet', $s->project], ['Rubrique', $s->category], ['Pays', $s->country ?: '—'], ['Contact', $s->name.' · '.$s->email]], $s->email, $s->name));
            }
            Notifier::send($s->email, new VoxMail('Nous avons bien reçu votre proposition', 'Merci, proposition reçue !', [
                'Bonjour '.e($s->name).',', 'Votre proposition « **'.e($s->project).'** » a bien été transmise à la rédaction de '.setting('name').'.',
                'Elle sera examinée selon nos critères éditoriaux. Si elle est retenue, un journaliste vous contactera pour préparer un portrait, une interview ou un reportage.'],
                null, ['Une soumission ne garantit pas la publication. La sélection est éditoriale et gratuite.']));
        }

        return redirect()->to(route('proposer').'#formulaire')->with('talent_sent', true);
    }

    public function advertise(Request $r)
    {
        $data = $r->validateWithBag('ads', [
            'company' => 'required|string|max:120', 'name' => 'required|string|max:100', 'email' => 'required|email:rfc|max:160',
            'format' => 'nullable|string|max:80', 'budget' => 'nullable|string|max:60', 'message' => 'required|string|min:10|max:3000',
        ], self::MSG);
        if (! $this->isSpam($r)) {
            $l = Lead::create($data + ['kind' => 'Annonceur', 'subject' => 'Demande de proposition'.(($data['format'] ?? null) ? ' · '.$data['format'] : '')]);
            if (setting('notify_forms')) {
                Notifier::send(Notifier::staff('email_partners', ['Administrateur', 'Commercial']), new VoxMail('Nouvelle demande annonceur : '.$l->company, 'Nouvelle demande d’un annonceur',
                    ['> '.e($l->message)], ['label' => 'Ouvrir la boîte de réception', 'url' => route('admin.inbox', ['onglet' => 'messages'])], [],
                    [['Structure', $l->company], ['Contact', $l->name.' · '.$l->email], ['Format', $l->format ?: 'Non défini'], ['Budget', $l->budget ?: 'Non défini']], $l->email, $l->name));
            }
            Notifier::send($l->email, new VoxMail('Votre demande de proposition commerciale', 'Merci pour votre demande', [
                'Bonjour '.e($l->name).',', 'Notre équipe partenariats a bien reçu votre demande pour **'.e($l->company).'**. Nous revenons vers vous sous 48 heures ouvrées avec une proposition adaptée.',
                'Rappel : chaque contenu payant est clairement signalé comme partenariat sur '.setting('name').'.'],
                ['label' => 'Voir nos offres', 'url' => route('annoncer')]));
        }

        return redirect()->to(route('annoncer').'#formulaire')->with('ads_sent', true);
    }

    public function contact(Request $r)
    {
        $data = $r->validateWithBag('contact', [
            'name' => 'required|string|max:100', 'email' => 'required|email:rfc|max:160',
            'subject' => 'required|string|max:160', 'message' => 'required|string|min:10|max:4000',
        ], self::MSG);
        if (! $this->isSpam($r)) {
            $l = Lead::create($data + ['kind' => 'Contact']);
            if (setting('notify_forms')) {
                Notifier::send(Notifier::staff(), new VoxMail('Contact : '.$l->subject, 'Nouveau message depuis le site',
                    ['**'.e($l->name).'** ('.e($l->email).') vous écrit :', '> '.nl2br(e($l->message))],
                    ['label' => 'Ouvrir la boîte de réception', 'url' => route('admin.inbox', ['onglet' => 'messages'])], [], [], $l->email, $l->name));
            }
            Notifier::send($l->email, new VoxMail('Nous avons bien reçu votre message', 'Message bien reçu', [
                'Bonjour '.e($l->name).',', 'Merci d’avoir écrit à la rédaction de '.setting('name').'. Votre message « '.e($l->subject).' » a bien été reçu ; nous vous répondrons dans les meilleurs délais.']));
        }

        return redirect()->to(route('contact').'#formulaire')->with('contact_sent', true);
    }

    public function publication(Request $r, Publication $publication)
    {
        if ($publication->price > 0) {
            $data = $r->validateWithBag('order', ['name' => 'required|string|max:100', 'email' => 'required|email:rfc|max:160', 'phone' => 'nullable|string|max:40'], self::MSG);
            $l = Lead::create(['kind' => 'Commande', 'name' => $data['name'], 'email' => $data['email'], 'subject' => 'Commande : '.$publication->title,
                'format' => $publication->type, 'budget' => fcfa($publication->price), 'message' => 'Commande de « '.$publication->title.' » ('.fcfa($publication->price).').'.(! empty($data['phone']) ? ' Téléphone : '.$data['phone'] : '')]);
            Notifier::send(Notifier::staff(), new VoxMail('Nouvelle commande : '.$publication->title, 'Nouvelle commande en bibliothèque', [], ['label' => 'Voir la commande', 'url' => route('admin.inbox', ['onglet' => 'messages'])], [],
                [['Publication', $publication->title], ['Prix', fcfa($publication->price)], ['Client', $l->name.' · '.$l->email], ['Téléphone', $data['phone'] ?? '—']], $l->email, $l->name));
            Notifier::send($l->email, new VoxMail('Votre commande : '.$publication->title, 'Merci pour votre commande', [
                'Bonjour '.e($l->name).',', 'Nous avons bien reçu votre commande de « **'.e($publication->title).'** » ('.fcfa($publication->price).'). Notre équipe vous contacte rapidement pour le paiement (Mobile Money, carte ou virement) et l’envoi.']));

            return redirect()->to($publication->url().'#obtenir')->with('pub_ok', 'Commande enregistrée ! Vous allez recevoir un e-mail de confirmation.');
        }
        $publication->increment('downloads');
        if ($publication->file_path && Storage::disk('local')->exists($publication->file_path)) {
            return redirect(URL::temporarySignedRoute('publication.download', now()->addMinutes(10), $publication));
        }

        return redirect()->to($publication->url().'#obtenir')->with('pub_ok', 'Le fichier sera disponible très prochainement. Inscrivez-vous à la newsletter pour être prévenu·e de sa sortie.');
    }

    public function download(Publication $publication)
    {
        abort_unless($publication->file_path && Storage::disk('local')->exists($publication->file_path), 404);

        return Storage::disk('local')->download($publication->file_path, $publication->slug.'.'.pathinfo($publication->file_path, PATHINFO_EXTENSION));
    }
}
