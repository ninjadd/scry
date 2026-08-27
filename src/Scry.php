<?php

namespace Scry;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Scry
{
    /**
     * The custom authorization callback.
     *
     * @var Closure|null
     */
    public static ?Closure $authUsing = null;

    /**
     * Default environments considered inherently trusted (developer-only, no network exposure risk).
     */
    protected const INHERENTLY_TRUSTED_ENVIRONMENTS = ['local', 'testing'];

    /**
     * Whether the silent-env-gate warning has already been logged this process.
     */
    protected static bool $warnedAboutEnvGate = false;

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

        $allowedEnvs = config('scry.allowed_environments', self::INHERENTLY_TRUSTED_ENVIRONMENTS);
        $environment = app()->environment();
        $allowed = in_array($environment, $allowedEnvs);

        if ($allowed && !in_array($environment, self::INHERENTLY_TRUSTED_ENVIRONMENTS) && !static::$warnedAboutEnvGate) {
            static::$warnedAboutEnvGate = true;
            Log::warning(
                "Scry is granting full unauthenticated database admin access in the '{$environment}' environment " .
                "based solely on config('scry.allowed_environments') — no Scry::auth() gate is configured. " .
                'Define a Scry::auth() closure to require real authorization outside local/testing.'
            );
        }

        return $allowed;
    }

    /**
     * Reset internal static state. Intended for use in tests only.
     */
    public static function resetForTesting(): void
    {
        static::$authUsing = null;
        static::$warnedAboutEnvGate = false;
    }
}
