<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Config/Database.php';

$path = sys_get_temp_dir() . '/sportiva-migration-' . bin2hex(random_bytes(6)) . '.sqlite';
$legacy = new SQLite3($path);
$legacy->exec(<<<SQL
CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'user');
CREATE TABLE fields (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, sport_type TEXT NOT NULL, price INTEGER NOT NULL, facilities TEXT NOT NULL, is_active INTEGER NOT NULL DEFAULT 1);
CREATE TABLE bookings (id INTEGER PRIMARY KEY AUTOINCREMENT, field_id INTEGER NOT NULL, customer_name TEXT NOT NULL, customer_email TEXT NOT NULL, booking_date TEXT NOT NULL, start_time TEXT NOT NULL, end_time TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'Menunggu pembayaran', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
SQL);
$legacy->close();
putenv('SPORTIVA_DB_PATH=' . $path);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};

try {
    $database = Database::connection();
    $tables = [];
    $tableResult = $database->query("SELECT name FROM sqlite_master WHERE type = 'table'");
    while ($row = $tableResult->fetchArray(SQLITE3_ASSOC)) {
        $tables[$row['name']] = true;
    }
    $columns = [];
    $columnResult = $database->query('PRAGMA table_info(bookings)');
    while ($row = $columnResult->fetchArray(SQLITE3_ASSOC)) {
        $columns[$row['name']] = true;
    }
    $assert(isset($tables['schedules'], $tables['payments']), 'tabel jadwal dan pembayaran harus dimigrasikan');
    $assert(isset($columns['user_id']), 'kolom kepemilikan booking harus ditambahkan ke database lama');
    $database->close();
    echo "P7 migrasi lulus: database lama ditambah tabel dan kolom tanpa kehilangan skema lama.\n";
} finally {
    putenv('SPORTIVA_DB_PATH');
    if (is_file($path)) {
        unlink($path);
    }
}
