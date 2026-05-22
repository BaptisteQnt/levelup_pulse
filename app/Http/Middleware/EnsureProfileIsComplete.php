<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Ensure the authenticated user has completed their profile before continuing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->is_profile_complete) {
            if ($request->expectsJson()) {
                abort(403, 'Votre profil doit être complété avant de poursuivre.');
            }

            return redirect()
                ->route('profile.edit')
                ->with('error', 'Complétez votre profil pour accéder à la souscription Premium.');
        }

        return $next($request);
    }
}
