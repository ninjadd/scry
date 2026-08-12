<?php

namespace Scry\Exceptions;

use InvalidArgumentException;

class UnsupportedDriverException extends InvalidArgumentException
{
    public static function forDriver(string $driver, ?string $connection = null): self
    {
        $message = "Database driver [{$driver}] is not supported by Scry Database Manager.";
        if ($connection) {
            $message .= " (Connection: [{$connection}])";
        }

        return new self($message);
    }
}
