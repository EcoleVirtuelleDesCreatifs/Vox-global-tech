<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VoxMail;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function index(Request $r)
    {
        $f = $r->query('statut', 'en attente');
        $comments = Comment::with('article')->when($f !== 'tous', fn ($q) => $q->where('status', $f))->latest()->paginate(30)->withQueryString();
        $counts = Comment::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.comments', ['comments' => $comments, 'f' => $f, 'counts' => $counts, 'total' => $counts->sum()]);
    }

    public function moderate(Request $r, Comment $comment)
    {
        $data = $r->validate(['status' => ['required', Rule::in(Comment::STATUSES)]]);
        $was = $comment->status;
        $comment->update($data);
        ActivityLog::record('a passé un commentaire de '.$comment->name.' en « '.$data['status'].' »');
        if ($data['status'] === 'approuvé' && $was !== 'approuvé' && ! $comment->user_id) {
            Notifier::send($comment->email, new VoxMail('Votre commentaire est publié', 'Merci pour votre réaction !', [
                'Bonjour '.e($comment->name).',', 'Votre commentaire sur « **'.e($comment->article->title).'** » a été relu et publié.'],
                ['label' => 'Voir la discussion', 'url' => $comment->article->url().'#commentaires']));
        }

        return back()->with('ok', 'Commentaire : '.$data['status'].'.');
    }

    public function reply(Request $r, Comment $comment)
    {
        $data = $r->validate(['text' => 'required|string|min:2|max:2000'], ['text.required' => 'Écrivez une réponse.']);
        $user = $r->user();
        $comment->replies()->create(['article_id' => $comment->article_id, 'user_id' => $user->id, 'name' => $user->name.' · '.setting('name'),
            'email' => $user->email, 'text' => $data['text'], 'status' => 'approuvé']);
        if ($comment->status === 'en attente') $comment->update(['status' => 'approuvé']);
        ActivityLog::record('a répondu à un commentaire de '.$comment->name);
        Notifier::send($comment->email, new VoxMail('La rédaction vous a répondu', 'Nouvelle réponse à votre commentaire', [
            'Bonjour '.e($comment->name).',', 'La rédaction a répondu à votre commentaire sur « **'.e($comment->article->title).'** » :', '> '.e($data['text'])],
            ['label' => 'Lire la réponse', 'url' => $comment->article->url().'#commentaires']));

        return back()->with('ok', 'Réponse publiée et envoyée par e-mail au lecteur.');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        ActivityLog::record('a supprimé un commentaire de '.$comment->name);

        return back()->with('ok', 'Commentaire supprimé.');
    }
}
