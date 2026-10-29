<?php

declare(strict_types=1);

final class Booking
{
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
        string $end
    ): string {
        $field = $this->database->prepare('SELECT id FROM fields WHERE id = :field_id AND is_active = 1');
        $field->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        if ($field->execute()->fetchArray(SQLITE3_ASSOC) === false) {
            return 'field_unavailable';
        }

        if (!$this->isTimeAvailable($fieldId, $date, $start, $end)) {
            return 'time_unavailable';
        }

        $statement = $this->database->prepare(<<<SQL
            INSERT INTO bookings (field_id, customer_name, customer_email, booking_date, start_time, end_time)
            VALUES (:field_id, :customer_name, :customer_email, :booking_date, :start_time, :end_time)
        SQL);
        $statement->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $statement->bindValue(':customer_name', $customerName, SQLITE3_TEXT);
        $statement->bindValue(':customer_email', $customerEmail, SQLITE3_TEXT);
        $statement->bindValue(':booking_date', $date, SQLITE3_TEXT);
        $statement->bindValue(':start_time', $start, SQLITE3_TEXT);
        $statement->bindValue(':end_time', $end, SQLITE3_TEXT);

        if ($statement->execute() === false) {
            throw new RuntimeException('Booking gagal disimpan ke database.');
        }

        return 'created';
    }
}
