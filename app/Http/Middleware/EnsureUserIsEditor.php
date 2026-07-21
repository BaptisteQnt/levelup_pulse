<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEditor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->is_editor && ! $user->is_admin && ! $user->is_super_admin)) {
            abort(403, 'Cette section est reservee aux redacteurs.');
        }

        return $next($request);
    }
}
