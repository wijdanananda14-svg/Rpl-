<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Models/Booking.php';

$database = new SQLite3(':memory:');
$database->exec(<<<SQL
CREATE TABLE fields (id INTEGER PRIMARY KEY, is_active INTEGER NOT NULL);
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    field_id INTEGER NOT NULL,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    booking_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL
);
INSERT INTO fields (id, is_active) VALUES (1, 1), (2, 1);
SQL);

$booking = new Booking($database);
$date = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$nextDate = (new DateTimeImmutable('tomorrow +1 day'))->format('Y-m-d');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};
$create = static fn (int $fieldId, string $bookingDate, string $start, string $end): string => $booking->create(
    $fieldId,
    'Pelanggan Uji',
    'pelanggan@example.com',
    $bookingDate,
    $start,
    $end
);

$assert($create(1, $date, '09:00', '10:00') === 'created', 'slot pertama yang tersedia harus berhasil dibuat');
$assert($create(1, $date, '09:30', '10:30') === 'time_unavailable', 'slot yang tumpang tindih harus ditolak');
$assert($create(1, $date, '10:00', '11:00') === 'created', 'slot yang tepat berdampingan harus diterima');
$assert($create(2, $date, '09:30', '10:30') === 'created', 'slot waktu sama pada lapangan lain harus diterima');
$assert($create(1, $nextDate, '09:30', '10:30') === 'created', 'slot waktu sama pada tanggal lain harus diterima');
$database->exec("UPDATE bookings SET status = 'Dibatalkan' WHERE id = 1");
$assert($create(1, $date, '09:00', '10:00') === 'created', 'slot dari booking yang dibatalkan harus tersedia kembali');

echo "P6 US4 lulus: overlap ditolak; slot berdampingan, tanggal/lapangan lain, dan slot batal tersedia.\n";
