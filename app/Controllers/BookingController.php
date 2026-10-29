<?php

declare(strict_types=1);

final class BookingController
{
    public function validate(array $input): array
    {
        $fieldId = filter_var(
            $input['field_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $name = trim((string) ($input['customer_name'] ?? ''));
        $email = trim((string) ($input['customer_email'] ?? ''));
        $date = (string) ($input['booking_date'] ?? '');
        $start = (string) ($input['start_time'] ?? '');
        $end = (string) ($input['end_time'] ?? '');

        if ($fieldId === false) {
            return ['valid' => false, 'message' => 'Lapangan wajib dipilih.'];
        }

        if ($name === '') {
            return ['valid' => false, 'message' => 'Nama pemesan wajib diisi.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email pemesan tidak valid.'];
        }

        $bookingDate = DateTime::createFromFormat('!Y-m-d', $date);
        $dateIsValid = $bookingDate !== false
            && $bookingDate->format('Y-m-d') === $date
            && $date >= date('Y-m-d');
        $timeIsValid = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) === 1
            && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end) === 1
            && $start < $end;

        if (!$dateIsValid || !$timeIsValid) {
            return ['valid' => false, 'message' => 'Tanggal dan waktu booking tidak valid.'];
        }

        return ['valid' => true, 'message' => 'Data booking valid.'];
    }
}
