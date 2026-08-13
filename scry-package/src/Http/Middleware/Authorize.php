<?php

namespace Scry\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Scry\Scry;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        if (! Scry::check($request)) {
            abort(403, 'Database Manager access is disabled for this environment.');
        }

        return $next($request);
    }
}
