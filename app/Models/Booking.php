<?php

declare(strict_types=1);

final class Booking
{
    public const STATUSES = ['Menunggu pembayaran', 'Dikonfirmasi', 'Dibatalkan'];

    public function __construct(private readonly SQLite3 $database)
    {
    }

    public function isTimeAvailable(int $fieldId, string $date, string $start, string $end): bool
    {
        $statement = $this->database->prepare(<<<SQL
            SELECT COUNT(*) FROM bookings
            WHERE field_id = :field_id AND booking_date = :booking_date
              AND status != 'Dibatalkan' AND start_time < :end_time AND end_time > :start_time
        SQL);
        $statement->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $statement->bindValue(':booking_date', $date, SQLITE3_TEXT);
        $statement->bindValue(':start_time', $start, SQLITE3_TEXT);
        $statement->bindValue(':end_time', $end, SQLITE3_TEXT);

        return (int) $statement->execute()->fetchArray()[0] === 0;
    }

    public function create(
        int $fieldId,
        string $customerName,
        string $customerEmail,
        string $date,
        string $start,
        string $end,
        ?int $userId = null
    ): string {
        $field = $this->database->prepare('SELECT id FROM fields WHERE id = :field_id AND is_active = 1');
        $field->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        if ($field->execute()->fetchArray(SQLITE3_ASSOC) === false) {
            return 'field_unavailable';
        }

        $scheduleCount = $this->database->prepare('SELECT COUNT(*) FROM schedules WHERE field_id = :field_id AND schedule_date = :booking_date');
        $scheduleCount->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $scheduleCount->bindValue(':booking_date', $date, SQLITE3_TEXT);
        $configuredSlots = (int) $scheduleCount->execute()->fetchArray()[0];
        if ($configuredSlots > 0) {
            $slot = $this->database->prepare(<<<SQL
                SELECT id FROM schedules
                WHERE field_id = :field_id AND schedule_date = :booking_date AND status = 'Tersedia'
                  AND start_time <= :start_time AND end_time >= :end_time
                LIMIT 1
            SQL);
            $slot->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
            $slot->bindValue(':booking_date', $date, SQLITE3_TEXT);
            $slot->bindValue(':start_time', $start, SQLITE3_TEXT);
            $slot->bindValue(':end_time', $end, SQLITE3_TEXT);
            if ($slot->execute()->fetchArray(SQLITE3_ASSOC) === false) {
                return 'outside_schedule';
            }
        }

        if (!$this->isTimeAvailable($fieldId, $date, $start, $end)) {
            return 'time_unavailable';
        }

        $statement = $this->database->prepare(<<<SQL
            INSERT INTO bookings (user_id, field_id, customer_name, customer_email, booking_date, start_time, end_time, status)
            VALUES (:user_id, :field_id, :customer_name, :customer_email, :booking_date, :start_time, :end_time, :status)
        SQL);
        $statement->bindValue(':user_id', $userId, $userId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $statement->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $statement->bindValue(':customer_name', $customerName, SQLITE3_TEXT);
        $statement->bindValue(':customer_email', $customerEmail, SQLITE3_TEXT);
        $statement->bindValue(':booking_date', $date, SQLITE3_TEXT);
        $statement->bindValue(':start_time', $start, SQLITE3_TEXT);
        $statement->bindValue(':end_time', $end, SQLITE3_TEXT);
        $statement->bindValue(':status', 'Menunggu pembayaran', SQLITE3_TEXT);

        if ($statement->execute() === false) {
            throw new RuntimeException('Booking gagal disimpan ke database.');
        }

        return 'created';
    }

    public function forUser(int $userId): SQLite3Result
    {
        $statement = $this->database->prepare(<<<SQL
            SELECT bookings.*, fields.name AS field_name, fields.price,
                   payments.status AS payment_status, payments.amount AS payment_amount
            FROM bookings
            JOIN fields ON fields.id = bookings.field_id
            LEFT JOIN payments ON payments.booking_id = bookings.id
            WHERE bookings.user_id = :user_id
            ORDER BY bookings.created_at DESC
        SQL);
        $statement->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        return $statement->execute();
    }

    public function cancel(int $bookingId, int $userId): bool
    {
        $statement = $this->database->prepare("UPDATE bookings SET status = 'Dibatalkan' WHERE id = :booking_id AND user_id = :user_id AND status = 'Menunggu pembayaran'");
        $statement->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $statement->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        if ($statement->execute() === false) {
            throw new RuntimeException('Booking gagal dibatalkan.');
        }
        return $this->database->changes() === 1;
    }

    public function updateStatus(int $bookingId, string $status): bool
    {
        if ($bookingId < 1 || !in_array($status, self::STATUSES, true)) {
            return false;
        }

        $exists = $this->database->prepare('SELECT 1 FROM bookings WHERE id = :id');
        $exists->bindValue(':id', $bookingId, SQLITE3_INTEGER);
        if ($exists->execute()->fetchArray() === false) {
            return false;
        }

        $statement = $this->database->prepare('UPDATE bookings SET status = :status WHERE id = :id');
        $statement->bindValue(':status', $status, SQLITE3_TEXT);
        $statement->bindValue(':id', $bookingId, SQLITE3_INTEGER);

        if ($statement->execute() === false) {
            throw new RuntimeException('Status booking gagal diperbarui.');
        }

        return true;
    }
}
