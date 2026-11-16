<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStaff
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->isStaff()) {
            return $user?->isClient() ? redirect()->route('portal.dashboard') : redirect()->route('login');
        }

        return $next($request);
    }
}
