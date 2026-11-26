<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode maintenance éditorial (Admin › Réglages) : les lecteurs reçoivent une page 503
 * avec Retry-After (Google conserve le classement), la rédaction connectée voit le site.
 */
class SiteMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (setting('maintenance') && ! $request->user()) {
            return response()->view('errors.503', [], 503, ['Retry-After' => '3600', 'X-Robots-Tag' => 'noindex']);
        }

        return $next($request);
    }
}
