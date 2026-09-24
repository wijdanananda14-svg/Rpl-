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
}
