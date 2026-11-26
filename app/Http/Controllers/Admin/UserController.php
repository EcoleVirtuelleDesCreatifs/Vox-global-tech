<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('author')->orderByDesc('active')->orderBy('name')->get();
        $counts = Article::selectRaw('author_id, COUNT(*) as n')->groupBy('author_id')->pluck('n', 'author_id');

        return view('admin.users', ['users' => $users, 'counts' => $counts, 'authors' => Author::orderBy('name')->get()]);
    }

    public function invite(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|unique:users,email', 'role' => ['required', Rule::in(User::ROLES)],
            'author' => 'nullable|boolean'], ['email.unique' => 'Un compte existe déjà avec cette adresse.']);
        $authorId = null;
        if ($r->boolean('author') && in_array($d['role'], ['Administrateur', 'Rédacteur en chef', 'Journaliste', 'Contributeur'], true)) {
            $authorId = Author::firstOrCreate(['slug' => Str::slug($d['name'])], ['name' => $d['name'], 'role' => $d['role'], 'bio' => ''])->id;
        }
        $u = User::create($d + ['password' => Str::random(40), 'active' => true, 'author_id' => $authorId]);
        $status = Password::sendResetLink(['email' => $u->email]);
        ActivityLog::record('a invité '.$u->name.' ('.$u->role.')');

        return back()->with('ok', $status === Password::RESET_LINK_SENT ? 'Invitation envoyée à '.$u->email.'.' : 'Compte créé ; l’e-mail d’invitation n’a pas pu partir.');
    }

    public function update(Request $r, User $user)
    {
        $d = $r->validate(['role' => ['nullable', Rule::in(User::ROLES)], 'active' => 'nullable|boolean', 'author_id' => 'nullable|exists:authors,id', 'resend' => 'nullable|boolean']);
        if ($user->is($r->user()) && (($d['role'] ?? $user->role) !== 'Administrateur' || $r->has('active') && ! $r->boolean('active'))) {
            return back()->with('err', 'Vous ne pouvez pas retirer vos propres droits d’administrateur.');
        }
        if ($r->boolean('resend')) {
            Password::sendResetLink(['email' => $user->email]);

            return back()->with('ok', 'Nouveau lien envoyé à '.$user->email.'.');
        }
        if (isset($d['role']) && $d['role'] !== $user->role) ActivityLog::record('a changé le rôle de '.$user->name.' en '.$d['role']);
        if ($r->has('active') && $r->boolean('active') !== $user->active) ActivityLog::record(($r->boolean('active') ? 'a réactivé ' : 'a désactivé ').$user->name);
        $user->update(array_filter(['role' => $d['role'] ?? null, 'author_id' => $r->has('author_id') ? ($d['author_id'] ?? null) : $user->author_id], fn ($v) => $v !== null) + ($r->has('active') ? ['active' => $r->boolean('active')] : []));

        return back()->with('ok', 'Utilisateur mis à jour.');
    }

    public function destroy(Request $r, User $user)
    {
        if ($user->is($r->user())) return back()->with('err', 'Vous ne pouvez pas supprimer votre propre compte.');
        if ($user->role === 'Administrateur' && User::where('role', 'Administrateur')->where('active', true)->count() <= 1) {
            return back()->with('err', 'Il doit rester au moins un administrateur actif.');
        }
        $user->delete();
        ActivityLog::record('a supprimé le compte de '.$user->name);

        return back()->with('ok', 'Compte supprimé.');
    }

    public function profile(Request $r)
    {
        return view('admin.profile', ['u' => $r->user()->load('author')]);
    }

    public function updateProfile(Request $r)
    {
        $u = $r->user();
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', Rule::unique('users')->ignore($u->id)],
            'bio' => 'nullable|string|max:600', 'same_as' => 'nullable|string|max:800',
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', PasswordRule::min(12)->letters()->numbers()]],
            ['current_password.current_password' => 'Mot de passe actuel incorrect.', 'password.confirmed' => 'La confirmation ne correspond pas.']);
        $u->fill(['name' => $d['name'], 'email' => $d['email']]);
        if (! empty($d['password'])) $u->password = $d['password'];
        $u->save();
        if ($u->author) {
            $u->author->update(['bio' => $d['bio'] ?? $u->author->bio, 'name' => $d['name'],
                'same_as' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($d['same_as'] ?? '')))))]);
        }
        ActivityLog::record('a mis à jour son profil'.(! empty($d['password']) ? ' et son mot de passe' : ''));
        if (! empty($d['password'])) {
            \App\Services\Notifier::send($u->email, new \App\Mail\VoxMail('Votre mot de passe a été modifié', 'Mot de passe modifié',
                ['Bonjour '.e($u->name).',', 'Le mot de passe de votre compte '.e(setting('name')).' vient d’être modifié.', 'Si vous n’êtes pas à l’origine de ce changement, réinitialisez-le immédiatement et prévenez un administrateur.'],
                ['label' => 'Réinitialiser mon mot de passe', 'url' => route('admin.password.request')], [],
                [['Date', now()->format('d/m/Y à H:i')], ['Adresse IP', (string) $r->ip()]]));
        }

        return back()->with('ok', 'Profil enregistré.');
    }
}
