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

$assert($controller->validate($input)['valid'], 'jadwal mendatang dengan jam valid harus diterima');
$assert(
    !$controller->validate(array_merge($input, ['booking_date' => (new DateTimeImmutable('yesterday'))->format('Y-m-d')]))['valid'],
    'tanggal lampau harus ditolak'
);
$invalidFutureDate = ((int) date('Y') + 1) . '-02-31';
$assert(
    !$controller->validate(array_merge($input, ['booking_date' => $invalidFutureDate]))['valid'],
    'tanggal yang tidak ada di kalender harus ditolak'
);
$assert(
    !$controller->validate(array_merge($input, ['start_time' => '24:00']))['valid'],
    'waktu di luar format 24 jam harus ditolak'
);
$assert(
    !$controller->validate(array_merge($input, [
        'booking_date' => date('Y-m-d'),
        'start_time' => date('H:i'),
    ]))['valid'],
    'waktu mulai hari ini yang sudah lewat atau tepat saat ini harus ditolak'
);

echo "P6 US2 lulus: tanggal dan format waktu divalidasi, waktu hari ini yang terlewat ditolak.\n";
