<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Point the default CRM connection at ledrix_demos for the shared sandbox tenant.
 * Sessions, jobs, and central/primary stay on their own connections.
 */
final class SandboxDatabase
{
    /** @var list<string> */
    private static array $stack = [];

    public static function connectionName(): string
    {
        return (string) config('sandbox.connection', 'demos_db');
    }

    public static function databaseName(): string
    {
        return (string) config('sandbox.database', 'ledrix_demos');
    }

    public static function activate(): void
    {
        self::$stack[] = (string) config('database.default', 'primary');
        Config::set('database.default', self::connectionName());
    }

    public static function deactivate(): void
    {
        $previous = array_pop(self::$stack);

        if ($previous !== null) {
            Config::set('database.default', $previous);
        }
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function using(callable $callback): mixed
    {
        self::activate();

        try {
            return $callback();
        } finally {
            self::deactivate();
        }
    }
}
