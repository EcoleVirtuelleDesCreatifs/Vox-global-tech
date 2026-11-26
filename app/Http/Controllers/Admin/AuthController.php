<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function login()
    {
        return view('admin.auth.login');
    }

    public function authenticate(Request $r)
    {
        $cred = $r->validate(['email' => 'required|email', 'password' => 'required'], ['required' => 'Ce champ est obligatoire.', 'email' => 'E-mail invalide.']);
        if (! Auth::attempt($cred + ['active' => true], $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Identifiants incorrects ou compte désactivé.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        $user = $r->user();
        $firstLogin = ! $user->last_login_at;
        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('s’est connecté·e', $user);
        $this->alertNewDevice($r, $user, $firstLogin);

        return redirect()->intended(route('admin.dashboard'));
    }

    /** Alerte de sécurité : connexion depuis un appareil / réseau jamais vu pour ce compte. */
    protected function alertNewDevice(Request $r, \App\Models\User $user, bool $firstLogin): void
    {
        $fingerprint = 'vox.login.'.$user->id.'.'.hash('sha256', $r->ip().'|'.$r->userAgent());
        if (\Illuminate\Support\Facades\Cache::has($fingerprint)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::forever($fingerprint, now()->toIso8601String());
        if ($firstLogin) {
            return;
        }
        \App\Services\Notifier::send($user->email, new \App\Mail\VoxMail(
            'Nouvelle connexion à votre compte '.setting('name'),
            'Nouvelle connexion détectée',
            ['Bonjour '.e($user->name).',', 'Une connexion à l’espace rédaction vient d’avoir lieu depuis un appareil ou un réseau que nous ne connaissions pas.', 'Si c’est bien vous, ignorez ce message. Sinon, réinitialisez immédiatement votre mot de passe.'],
            ['label' => 'Réinitialiser mon mot de passe', 'url' => route('admin.password.request')], [],
            [['Date', now()->format('d/m/Y à H:i')], ['Adresse IP', (string) $r->ip()], ['Navigateur', mb_substr((string) $r->userAgent(), 0, 90)]],
        ));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('admin.login')->with('ok', 'Vous êtes déconnecté·e.');
    }

    public function forgot()
    {
        return view('admin.auth.forgot');
    }

    public function sendReset(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));

        // Réponse identique que le compte existe ou non (pas de divulgation)
        return back()->with('ok', 'Si un compte correspond à cette adresse, un lien vient d’être envoyé.');
    }

    public function reset(Request $r, string $token)
    {
        return view('admin.auth.reset', ['token' => $token, 'email' => $r->query('email')]);
    }

    public function updatePassword(Request $r)
    {
        $r->validate([
            'token' => 'required', 'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()],
        ], ['password.confirmed' => 'Les deux mots de passe ne correspondent pas.', 'password.min' => '12 caractères minimum.']);

        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Ce lien est invalide ou expiré. Demandez-en un nouveau.']);
        }
        $user = User::where('email', $r->email)->first();
        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('a défini son mot de passe', $user);

        return redirect()->route('admin.dashboard')->with('ok', 'Mot de passe enregistré. Bienvenue !');
    }
}
