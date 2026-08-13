<?php

namespace Scry;

use Closure;
use Illuminate\Http\Request;

class Scry
{
    /**
     * The custom authorization callback.
     *
     * @var Closure|null
     */
    public static ?Closure $authUsing = null;

    /**
     * Configure authorization callback for Scry.
     *
     * @param Closure $callback
     * @return static
     */
    public static function auth(Closure $callback): static
    {
        static::$authUsing = $callback;
        return new static();
    }

    /**
     * Determine if the given request is authorized to access Scry.
     *
     * @param Request $request
     * @return bool
     */
    public static function check(Request $request): bool
    {
        if (static::$authUsing) {
            return (bool) call_user_func(static::$authUsing, $request);
        }

        $allowedEnvs = config('scry.allowed_environments', ['local', 'testing']);

        return in_array(app()->environment(), $allowedEnvs);
    }
}
