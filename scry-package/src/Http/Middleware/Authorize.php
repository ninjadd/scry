<?php

namespace Scry\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        $allowedEnvs = config('scry.allowed_environments', config('database-manager.allowed_environments', ['local', 'testing']));

        if (! in_array(app()->environment(), $allowedEnvs)) {
            abort(403, 'Database Manager access is disabled for this environment.');
        }

        return $next($request);
    }
}
