<?php

// app/Http/Middleware/EnsureUserIsSubscribed.php
namespace App\Http\Middleware;

use Closure;

class EnsureUserIsSubscribed
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->subscribed('default')) {
            return redirect()->route('billing.plans')
                ->with('error', 'Vous devez être abonné pour accéder à cette page.');
        }
        return $next($request);
    }
}

