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
    status TEXT NOT NULL DEFAULT 'Menunggu pembayaran'
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
$create = static fn (int $fieldId, string $start, string $end): string => $booking->create(
    $fieldId,
    'Tester',
    'tester@example.com',
    $date,
    $start,
    $end
);

$assert($create(1, '09:00', '10:00') === 'created', 'slot tersedia harus berhasil dipesan');
$assert($create(1, '09:30', '10:30') === 'time_unavailable', 'slot yang tumpang tindih harus ditolak');
$assert($create(1, '10:00', '11:00') === 'created', 'slot yang tepat berdampingan harus diterima');
$assert($create(2, '11:00', '12:00') === 'field_unavailable', 'lapangan nonaktif harus ditolak');
$database->exec("UPDATE bookings SET status = 'Dibatalkan' WHERE id = 1");
$assert($create(1, '09:00', '10:00') === 'created', 'slot booking yang dibatalkan harus tersedia kembali');
$assert((int) $database->querySingle('SELECT COUNT(*) FROM bookings') === 3, 'hanya booking yang berhasil dibuat tersimpan');

echo "Alur booking berhasil: ketersediaan, bentrok, lapangan nonaktif, dan pembatalan terverifikasi.\n";
?>
