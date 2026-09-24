<?php

declare(strict_types=1);

final class BookingController
{
    public function validate(array $input): array
    {
        $date = (string) ($input['booking_date'] ?? '');
        $start = (string) ($input['start_time'] ?? '');
        $end = (string) ($input['end_time'] ?? '');
        $dateIsValid = DateTime::createFromFormat('Y-m-d', $date) !== false;
        $timeIsValid = $start !== '' && $end !== '' && $start < $end;

        if (trim((string) ($input['customer_name'] ?? '')) === '') {
            return ['valid' => false, 'message' => 'Nama pemesan wajib diisi.'];
        }

        if (!filter_var($input['customer_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email pemesan tidak valid.'];
        }

        if (!$dateIsValid || !$timeIsValid) {
            return ['valid' => false, 'message' => 'Tanggal dan waktu booking tidak valid.'];
        }

        return ['valid' => true, 'message' => 'Data booking valid.'];
    }
}
