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
    $assert($booking->updateStatus(1, $status), "status '{$status}' yang diizinkan harus tersimpan");
    $storedStatus = $database->querySingle('SELECT status FROM bookings WHERE id = 1');
    $assert($storedStatus === $status, 'status yang disimpan harus sama dengan pilihan admin');
}

$assert(!$booking->updateStatus(1, 'Status tidak dikenal'), 'status di luar pilihan harus ditolak');
$assert(!$booking->updateStatus(0, 'Dikonfirmasi'), 'ID booking tidak valid harus ditolak');
$assert(!$booking->updateStatus(999, 'Dikonfirmasi'), 'booking yang tidak ditemukan harus ditolak');
$assert(
    $database->querySingle('SELECT status FROM bookings WHERE id = 1') === 'Dibatalkan',
    'input yang ditolak tidak boleh mengubah status tersimpan'
);

echo "Status booking berhasil: pilihan valid tersimpan dan input tidak valid ditolak.\n";
?>
