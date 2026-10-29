<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Models/Booking.php';

$database = new SQLite3(':memory:');
$database->exec(<<<SQL
CREATE TABLE fields (id INTEGER PRIMARY KEY, is_active INTEGER NOT NULL);
CREATE TABLE schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    field_id INTEGER NOT NULL,
    schedule_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Tersedia'
);
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    field_id INTEGER NOT NULL,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    booking_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL
);
INSERT INTO fields (id, is_active) VALUES (1, 1), (2, 0);
SQL);

$booking = new Booking($database);
$date = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};
$create = static fn (int $fieldId): string => $booking->create(
    $fieldId,
    'Pelanggan Uji',
    'pelanggan@example.com',
    $date,
    '09:00',
    '10:00'
);

$assert($create(1) === 'created', 'booking pada lapangan aktif harus dibuat');
$assert(
    $database->querySingle('SELECT status FROM bookings WHERE field_id = 1') === 'Menunggu pembayaran',
    'booking baru harus eksplisit tersimpan dengan status menunggu pembayaran'
);
$assert($create(2) === 'field_unavailable', 'lapangan nonaktif harus ditolak');
$assert($create(999) === 'field_unavailable', 'ID lapangan yang tidak ada harus ditolak');
$assert((int) $database->querySingle('SELECT COUNT(*) FROM bookings') === 1, 'booking yang ditolak tidak boleh tersimpan');

echo "P6 US3 lulus: booking lapangan aktif disimpan dengan status awal; lapangan tidak tersedia ditolak.\n";
