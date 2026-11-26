<?php

namespace App\Http\Middleware;

use App\Services\Analytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Enregistre la visite (ou le passage d'un robot) après le rendu, et injecte l'identifiant de vue dans la page. */
class TrackPageView
{
    public function __construct(protected Analytics $analytics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('vox.bot', $this->analytics->detectBot($request->userAgent()) !== null);
        $response = $next($request);

        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && ! $request->user()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            try {
                $id = $this->analytics->record($request, $request->attributes->get('vox.article'));
                if ($id && method_exists($response, 'setContent')) {
                    $response->setContent(str_replace('data-pv="0"', 'data-pv="'.$id.'"', (string) $response->getContent()));
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $response;
    }
}
