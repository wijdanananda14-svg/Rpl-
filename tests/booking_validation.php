<?php

require_once __DIR__ . '/../app/Controllers/BookingController.php';

$controller = new BookingController();
$result = $controller->validate([
    'customer_name' => 'Tester',
    'customer_email' => 'tester@example.com',
    'booking_date' => date('Y-m-d'),
    'start_time' => '09:00',
    'end_time' => '10:00',
]);

if (!$result['valid']) {
    fwrite(STDERR, "Validasi booking gagal.\n");
    exit(1);
}

echo "Validasi booking berhasil.\n";
