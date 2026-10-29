<?php

require_once __DIR__ . '/../app/Controllers/BookingController.php';

$controller = new BookingController();
$validInput = [
    'field_id' => '1',
    'customer_name' => 'Tester',
    'customer_email' => 'tester@example.com',
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

$assert($controller->validate($validInput)['valid'], 'data booking valid harus diterima');
$assert(!$controller->validate(array_merge($validInput, ['field_id' => '0']))['valid'], 'lapangan harus dipilih');
$assert(!$controller->validate(array_merge($validInput, ['customer_email' => 'invalid']))['valid'], 'email tidak valid harus ditolak');
$invalidFutureDate = ((int) date('Y') + 1) . '-02-31';
$assert(!$controller->validate(array_merge($validInput, ['booking_date' => $invalidFutureDate]))['valid'], 'tanggal kalender tidak valid harus ditolak');
$assert(
    !$controller->validate(array_merge($validInput, ['booking_date' => (new DateTimeImmutable('yesterday'))->format('Y-m-d')]))['valid'],
    'tanggal yang sudah lewat harus ditolak'
);
$assert(!$controller->validate(array_merge($validInput, ['start_time' => '25:00']))['valid'], 'jam di luar rentang harus ditolak');
$assert(!$controller->validate(array_merge($validInput, ['end_time' => '09:00']))['valid'], 'waktu selesai harus setelah waktu mulai');

echo "Validasi booking berhasil: data valid diterima dan input tidak valid ditolak.\n";
?>
