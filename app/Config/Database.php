<?php

declare(strict_types=1);

final class Database
{
    public static function connection(): SQLite3
    {
        $storagePath = dirname(__DIR__, 2) . '/storage/reservasi.sqlite';
        $database = new SQLite3($storagePath);
        $database->exec('PRAGMA foreign_keys = ON');

        return $database;
    }
}
