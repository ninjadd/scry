<?php

namespace Scry\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Scry\Scry auth(\Closure $callback)
 * @method static bool check(\Illuminate\Http\Request $request)
 *
 * @see \Scry\Scry
 */
class Scry extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'scry';
    }
}
