<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Models/Payment.php';
require_once __DIR__ . '/../app/Models/Booking.php';

$database = new SQLite3(':memory:');
$database->enableExceptions(true);
$database->exec(<<<SQL
CREATE TABLE fields (id INTEGER PRIMARY KEY, price INTEGER NOT NULL);
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    field_id INTEGER NOT NULL,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    booking_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL
);
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL UNIQUE,
    method TEXT NOT NULL,
    amount INTEGER NOT NULL,
    status TEXT NOT NULL,
    paid_at TEXT
);
INSERT INTO fields VALUES (1, 120000);
INSERT INTO bookings VALUES (1, 10, 1, 'Pemilik', 'owner@example.com', '2026-11-01', '09:00', '10:30', 'Menunggu pembayaran');
INSERT INTO bookings VALUES (2, 20, 1, 'Lain', 'other@example.com', '2026-11-01', '11:00', '12:00', 'Menunggu pembayaran');
INSERT INTO bookings VALUES (3, 10, 1, 'Lunas', 'owner@example.com', '2026-11-01', '13:00', '14:00', 'Dikonfirmasi');
SQL);

$payment = new Payment($database);
$booking = new Booking($database);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};

$assert(!$payment->simulate(1, 20, 'Transfer simulasi'), 'pengguna lain tidak dapat membayar booking');
$assert(!$payment->simulate(1, 10, 'Kartu kredit'), 'metode pembayaran di luar pilihan ditolak');
$assert($payment->simulate(1, 10, 'Transfer simulasi'), 'pembayaran simulasi valid harus berhasil');
$assert(
    $database->querySingle('SELECT amount FROM payments WHERE booking_id = 1') === 180000,
    'total pembayaran harus sesuai tarif 1,5 jam'
);
$assert($database->querySingle("SELECT status FROM bookings WHERE id = 1") === 'Dikonfirmasi', 'pembayaran mengonfirmasi booking');
$assert(!$payment->simulate(1, 10, 'Transfer simulasi'), 'booking tidak boleh dibayar dua kali');
$assert(!$booking->cancel(1, 10), 'booking terkonfirmasi tidak boleh dibatalkan oleh pelanggan');
$assert($booking->cancel(2, 20), 'pemilik dapat membatalkan booking menunggu pembayaran');
$assert(!$booking->cancel(2, 10), 'akun lain tidak dapat membatalkan booking');

echo "P7 pembayaran/pembatalan lulus: kepemilikan, nominal, konfirmasi, duplikasi, dan aturan batal.\n";
