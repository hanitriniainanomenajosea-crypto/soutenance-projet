<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckServiceAccess
{
    /**
     * Gère l'accès restreint selon le service de l'utilisateur.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // L'administrateur a un accès global
        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        // Vérification qu'un agent possède bien un service valide
        if ($user && $user->role === 'agent' && !$user->service_id) {
            abort(403, 'Accès refusé : Aucun service ne vous est attribué.');
        }

        return $next($request);
    }
}
