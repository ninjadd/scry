<?php

namespace Scry\DatabaseManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Authorize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedEnvs = config('database-manager.allowed_environments', ['local']);

        if (! in_array(app()->environment(), $allowedEnvs)) {
            abort(403, 'Database Manager access is disabled for this environment.');
        }

        return $next($request);
    }
}
