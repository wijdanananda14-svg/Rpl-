<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Models/Field.php';
require_once __DIR__ . '/../app/Models/Schedule.php';
require_once __DIR__ . '/../app/Models/Booking.php';

$database = new SQLite3(':memory:');
$database->enableExceptions(true);
$database->exec(<<<SQL
CREATE TABLE fields (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, sport_type TEXT NOT NULL, price INTEGER NOT NULL, facilities TEXT NOT NULL, is_active INTEGER NOT NULL DEFAULT 1);
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
INSERT INTO fields (name, sport_type, price, facilities) VALUES ('Arena', 'Futsal', 120000, 'Lampu');
SQL);
$fields = new Field($database);
$schedules = new Schedule($database);
$booking = new Booking($database);
$date = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};

$assert(!$fields->save(null, '', 'Futsal', 100, ''), 'nama lapangan kosong ditolak');
$assert(!$fields->save(null, 'Harga invalid', 'Futsal', 0, ''), 'harga nol ditolak');
$assert($fields->save(null, 'Arena 2', 'Basket', 100000, 'Ring'), 'admin dapat menambah lapangan');
$assert($fields->save(1, 'Arena Updated', 'Futsal', 150000, 'Lampu, parkir'), 'admin dapat memperbarui lapangan');
$assert($fields->setActive(1, false), 'admin dapat menonaktifkan lapangan');
$assert($booking->create(1, 'Uji', 'uji@example.com', $date, '09:00', '10:00') === 'field_unavailable', 'lapangan nonaktif tidak bisa dibooking');
$assert($fields->setActive(1, true), 'admin dapat mengaktifkan lapangan');
$assert($schedules->create(1, $date, '09:00', '12:00'), 'admin dapat menambah jadwal valid');
$assert(!$schedules->create(1, $date, '11:00', '13:00'), 'jadwal yang overlap ditolak');
$assert($booking->create(1, 'Uji', 'uji@example.com', $date, '08:00', '09:00') === 'outside_schedule', 'booking di luar jadwal admin ditolak');
$assert($booking->create(1, 'Uji', 'uji@example.com', $date, '09:00', '10:00') === 'created', 'booking di dalam jadwal admin diterima');
$assert($schedules->setStatus(1, 'Ditutup'), 'admin dapat menutup jadwal');
$assert($booking->create(1, 'Uji', 'uji@example.com', $date, '10:00', '11:00') === 'outside_schedule', 'booking saat jadwal tertutup ditolak');

echo "P7 pengelolaan lapangan/jadwal lulus: CRUD, validasi overlap, dan enforcement jadwal.\n";
