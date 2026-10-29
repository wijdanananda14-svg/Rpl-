<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Controllers/BookingController.php';

$controller = new BookingController();
$input = [
    'field_id' => '1',
    'customer_name' => 'Pelanggan Uji',
    'customer_email' => 'pelanggan@example.com',
    'booking_date' => (new DateTimeImmutable('tomorrow'))->format('Y-m-d'),
    'start_time' => '09:00',
    'end_time' => '10:00',
];
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};

$assert($controller->validate($input)['valid'], 'data pelanggan lengkap dan valid harus diterima');
$assert(
    !$controller->validate(array_merge($input, ['customer_name' => '   ']))['valid'],
    'nama kosong setelah trim harus ditolak'
);
$assert(
    !$controller->validate(array_merge($input, ['customer_email' => 'bukan-email']))['valid'],
    'format email tidak valid harus ditolak'
);

echo "P6 US1 lulus: data pelanggan valid diterima, nama kosong dan email salah ditolak.\n";
