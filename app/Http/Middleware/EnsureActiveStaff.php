<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Un compte désactivé est déconnecté immédiatement. */
class EnsureActiveStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')->withErrors(['email' => 'Ce compte est désactivé. Contactez un administrateur.']);
        }

        return $next($request);
    }
}
