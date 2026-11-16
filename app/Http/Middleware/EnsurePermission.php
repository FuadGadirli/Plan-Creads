<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$perms)
    {
        $user = $request->user();
        foreach ($perms as $perm) {
            if ($user && $user->hasPerm($perm)) {
                return $next($request);
            }
        }
        abort(403, 'Bu bölməyə giriş icazəniz yoxdur.');
    }
}
