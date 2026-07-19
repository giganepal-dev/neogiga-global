<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sql
{
    /**
     * Case-insensitive LIKE operator for the active DB driver.
     *
     * Postgres has a native ILIKE; SQLite/MySQL LIKE is already
     * case-insensitive for ASCII, so 'like' is the correct fallback.
     * Using 'ilike' on a non-Postgres driver is a syntax error (HTTP 500).
     */
    public static function ilike(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
