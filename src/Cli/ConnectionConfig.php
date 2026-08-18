<?php

namespace Scry\Cli;

use InvalidArgumentException;

class ConnectionConfig
{
    /**
     * Parse input into one or more connection arrays suitable for Capsule.
     *
     * @param string|null $target Target file path, DSN, or connection name
     * @param array $options CLI options (--driver, --host, --port, --database, --username, --password, --env)
     * @return array Array of [connectionName => connectionConfigArray]
     */
    public static function resolveConnections(?string $target = null, array $options = []): array
    {
        $connections = [];

        // 1. If explicit CLI flags are provided
        if (!empty($options['driver'])) {
            $connections['default'] = self::fromFlags($options);
            return $connections;
        }

        // 2. If target is provided (file path, DSN, or SQLite)
        if (!empty($target)) {
            if (self::isDsn($target)) {
                $connections['default'] = self::fromDsn($target);
                return $connections;
            }

            if (self::isSqliteFile($target)) {
                $connections['default'] = self::fromSqlitePath($target);
                return $connections;
            }
        }

        // 3. Check for specified or current working directory .env file
        $envPath = $options['env'] ?? getcwd() . '/.env';
        if (file_exists($envPath)) {
            $envConnections = self::fromEnvFile($envPath);
            if (!empty($envConnections)) {
                return $envConnections;
            }
        }

        // 4. Check for common SQLite database files in current directory
        $commonSqliteFiles = [
            'database.sqlite',
            'database/database.sqlite',
            'app.db',
            'data.db',
            'db.sqlite',
            'db.sqlite3',
        ];

        foreach ($commonSqliteFiles as $file) {
            $fullPath = getcwd() . '/' . $file;
            if (file_exists($fullPath)) {
                $connections['default'] = self::fromSqlitePath($fullPath);
                return $connections;
            }
        }

        // 5. Fallback default in-memory SQLite database
        $connections['default'] = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        return $connections;
    }

    /**
     * Check if a string looks like a DSN (e.g. mysql://..., postgres://..., sqlite://...).
     */
    public static function isDsn(string $target): bool
    {
        return (bool) preg_match('/^(mysql|mariadb|postgres|postgresql|pgsql|sqlite|sqlite3|sqlsrv|mssql):\/\//i', $target);
    }

    /**
     * Check if a string looks like an SQLite file path.
     */
    public static function isSqliteFile(string $target): bool
    {
        if ($target === ':memory:') {
            return true;
        }

        if (self::isDsn($target)) {
            return false;
        }

        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        return in_array($ext, ['sqlite', 'sqlite3', 'db', 'sdb', 'sqlitedb']) || (@file_exists($target));
    }

    /**
     * Convert SQLite file path to connection configuration.
     */
    public static function fromSqlitePath(string $path): array
    {
        $realPath = $path === ':memory:' ? ':memory:' : realpath($path);
        if ($path !== ':memory:' && !$realPath) {
            // If file doesn't exist yet, make absolute path relative to CWD
            $realPath = str_starts_with($path, '/') ? $path : getcwd() . '/' . ltrim($path, './');
        }

        return [
            'driver' => 'sqlite',
            'database' => $realPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];
    }

    /**
     * Parse DSN string into connection configuration.
     *
     * Example: postgres://user:secret@localhost:5432/my_db?sslmode=prefer
     */
    public static function fromDsn(string $dsn): array
    {
        $parsed = parse_url($dsn);
        if (!$parsed || empty($parsed['scheme'])) {
            throw new InvalidArgumentException("Invalid DSN format: {$dsn}");
        }

        $scheme = strtolower($parsed['scheme']);
        $driver = match ($scheme) {
            'postgres', 'postgresql', 'pgsql' => 'pgsql',
            'mysql' => 'mysql',
            'mariadb' => 'mariadb',
            'sqlite', 'sqlite3' => 'sqlite',
            'sqlsrv', 'mssql' => 'sqlsrv',
            default => throw new InvalidArgumentException("Unsupported database driver scheme: {$scheme}"),
        };

        if ($driver === 'sqlite') {
            $path = ($parsed['host'] ?? '') . ($parsed['path'] ?? '');
            if (empty($path)) {
                $path = ':memory:';
            }
            return self::fromSqlitePath($path);
        }

        $database = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
        $host = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? match ($driver) {
            'pgsql' => 5432,
            'mysql', 'mariadb' => 3306,
            'sqlsrv' => 1433,
            default => 3306,
        };

        $username = $parsed['user'] ?? '';
        $password = $parsed['pass'] ?? '';

        $config = [
            'driver' => $driver,
            'host' => $host,
            'port' => (int) $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix' => '',
        ];

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $config['collation'] = 'utf8mb4_unicode_ci';
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            if (isset($queryParams['sslmode'])) {
                $config['sslmode'] = $queryParams['sslmode'];
            }
            if (isset($queryParams['schema'])) {
                $config['schema'] = $queryParams['schema'];
            }
        }

        return $config;
    }

    /**
     * Convert CLI flags into connection configuration.
     */
    public static function fromFlags(array $options): array
    {
        $driver = strtolower($options['driver'] ?? 'mysql');
        $normalizedDriver = match ($driver) {
            'postgres', 'postgresql', 'pgsql' => 'pgsql',
            'mysql' => 'mysql',
            'mariadb' => 'mariadb',
            'sqlite', 'sqlite3' => 'sqlite',
            'sqlsrv', 'mssql', 'sqlserver' => 'sqlsrv',
            default => $driver,
        };

        if ($normalizedDriver === 'sqlite') {
            return self::fromSqlitePath($options['database'] ?? ':memory:');
        }

        return [
            'driver' => $normalizedDriver,
            'host' => $options['host'] ?? '127.0.0.1',
            'port' => (int) ($options['port'] ?? match ($normalizedDriver) {
                'pgsql' => 5432,
                'mysql', 'mariadb' => 3306,
                'sqlsrv' => 1433,
                default => 3306,
            }),
            'database' => $options['database'] ?? '',
            'username' => $options['username'] ?? ($normalizedDriver === 'pgsql' ? 'postgres' : 'root'),
            'password' => $options['password'] ?? '',
            'charset' => $normalizedDriver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'collation' => ($normalizedDriver === 'mysql' || $normalizedDriver === 'mariadb') ? 'utf8mb4_unicode_ci' : null,
            'prefix' => '',
        ];
    }

    /**
     * Parse a .env file and extract database connections.
     */
    public static function fromEnvFile(string $envPath): array
    {
        if (!file_exists($envPath)) {
            return [];
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip enclosing quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $env[$key] = $value;
        }

        $connections = [];

        // Check for DATABASE_URL DSN
        if (!empty($env['DATABASE_URL'])) {
            try {
                $connections['default'] = self::fromDsn($env['DATABASE_URL']);
                return $connections;
            } catch (\Throwable) {
                // fallback to individual vars
            }
        }

        // Standard Laravel / generic .env DB variables
        $defaultDriver = $env['DB_CONNECTION'] ?? 'mysql';
        $normalizedDriver = match (strtolower($defaultDriver)) {
            'postgres', 'postgresql', 'pgsql' => 'pgsql',
            'mysql' => 'mysql',
            'mariadb' => 'mariadb',
            'sqlite', 'sqlite3' => 'sqlite',
            'sqlsrv', 'mssql' => 'sqlsrv',
            default => $defaultDriver,
        };

        if ($normalizedDriver === 'sqlite') {
            $dbPath = $env['DB_DATABASE'] ?? 'database/database.sqlite';
            if ($dbPath !== ':memory:' && !str_starts_with($dbPath, '/')) {
                $dbPath = dirname($envPath) . '/' . $dbPath;
            }
            $connections['default'] = self::fromSqlitePath($dbPath);
            return $connections;
        }

        $connections['default'] = [
            'driver' => $normalizedDriver,
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'port' => (int) ($env['DB_PORT'] ?? match ($normalizedDriver) {
                'pgsql' => 5432,
                'mysql', 'mariadb' => 3306,
                'sqlsrv' => 1433,
                default => 3306,
            }),
            'database' => $env['DB_DATABASE'] ?? '',
            'username' => $env['DB_USERNAME'] ?? '',
            'password' => $env['DB_PASSWORD'] ?? '',
            'charset' => $normalizedDriver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'collation' => ($normalizedDriver === 'mysql' || $normalizedDriver === 'mariadb') ? 'utf8mb4_unicode_ci' : null,
            'prefix' => '',
        ];

        return $connections;
    }
}
