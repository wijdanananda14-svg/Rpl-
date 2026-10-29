<?php

declare(strict_types=1);

final class Database
{
    public static function connection(): SQLite3
    {
        $storagePath = getenv('SPORTIVA_DB_PATH') ?: dirname(__DIR__, 2) . '/storage/reservasi.sqlite';
        $database = new SQLite3($storagePath);
        $database->exec('PRAGMA foreign_keys = ON');
        $database->enableExceptions(true);
        $database->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user'
);
CREATE TABLE IF NOT EXISTS fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    sport_type TEXT NOT NULL,
    price INTEGER NOT NULL,
    facilities TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    field_id INTEGER NOT NULL,
    schedule_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Tersedia',
    FOREIGN KEY (field_id) REFERENCES fields(id)
);
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    field_id INTEGER NOT NULL,
    schedule_id INTEGER,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    booking_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Menunggu pembayaran',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (field_id) REFERENCES fields(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL UNIQUE,
    method TEXT NOT NULL,
    amount INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'Menunggu',
    paid_at TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
SQL);
        self::addColumnIfMissing($database, 'bookings', 'user_id', 'INTEGER REFERENCES users(id)');

        return $database;
    }

    private static function addColumnIfMissing(SQLite3 $database, string $table, string $column, string $definition): void
    {
        $columns = $database->query("PRAGMA table_info({$table})");
        while ($row = $columns->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] === $column) {
                return;
            }
        }

        $database->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}
