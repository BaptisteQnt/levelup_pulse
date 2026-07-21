<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->is_super_admin && ! $user->is_security_officer)) {
            abort(403, 'Cette section est reservee au super administrateur et au responsable securite.');
        }

        return $next($request);
    }
}
