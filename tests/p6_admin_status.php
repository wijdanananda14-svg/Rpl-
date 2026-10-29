<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Models/Booking.php';

$database = new SQLite3(':memory:');
$database->exec(<<<SQL
CREATE TABLE bookings (id INTEGER PRIMARY KEY, status TEXT NOT NULL);
INSERT INTO bookings (id, status) VALUES (1, 'Menunggu pembayaran');
SQL);

$booking = new Booking($database);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};

foreach (Booking::STATUSES as $status) {
    $assert($booking->updateStatus(1, $status), "status sah '{$status}' harus diterima");
    $assert(
        $database->querySingle('SELECT status FROM bookings WHERE id = 1') === $status,
        'status tersimpan harus sesuai nilai yang dipilih'
    );
}

$assert(!$booking->updateStatus(1, 'Status tidak dikenal'), 'status di luar daftar harus ditolak');
$assert(!$booking->updateStatus(0, 'Dikonfirmasi'), 'ID booking tidak valid harus ditolak');
$assert(!$booking->updateStatus(999, 'Dikonfirmasi'), 'booking yang tidak ada harus ditolak');
$assert(
    $database->querySingle('SELECT status FROM bookings WHERE id = 1') === 'Dibatalkan',
    'update yang ditolak tidak boleh mengubah status tersimpan'
);

echo "P6 US5 lulus: status sah tersimpan dan status/ID yang tidak sah ditolak.\n";
