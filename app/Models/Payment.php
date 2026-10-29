<?php

declare(strict_types=1);

final class Payment
{
    public function __construct(private readonly SQLite3 $database)
    {
    }

    public function simulate(int $bookingId, int $userId, string $method): bool
    {
        $allowedMethods = ['Transfer simulasi', 'E-wallet simulasi'];
        if ($bookingId < 1 || !in_array($method, $allowedMethods, true)) {
            return false;
        }

        $bookingStatement = $this->database->prepare(<<<SQL
            SELECT bookings.id, bookings.field_id, bookings.start_time, bookings.end_time, bookings.status, fields.price
            FROM bookings JOIN fields ON fields.id = bookings.field_id
            WHERE bookings.id = :booking_id AND bookings.user_id = :user_id
        SQL);
        $bookingStatement->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $bookingStatement->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $booking = $bookingStatement->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$booking || $booking['status'] !== 'Menunggu pembayaran') {
            return false;
        }

        $durationHours = (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600;
        if ($durationHours <= 0) {
            return false;
        }
        $amount = (int) ceil((int) $booking['price'] * $durationHours);

        $this->database->exec('BEGIN IMMEDIATE');
        try {
            $payment = $this->database->prepare(<<<SQL
                INSERT INTO payments (booking_id, method, amount, status, paid_at)
                VALUES (:booking_id, :method, :amount, 'Lunas', CURRENT_TIMESTAMP)
            SQL);
            $payment->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
            $payment->bindValue(':method', $method, SQLITE3_TEXT);
            $payment->bindValue(':amount', $amount, SQLITE3_INTEGER);
            $payment->execute();

            $update = $this->database->prepare("UPDATE bookings SET status = 'Dikonfirmasi' WHERE id = :booking_id AND user_id = :user_id AND status = 'Menunggu pembayaran'");
            $update->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
            $update->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $update->execute();
            if ($this->database->changes() !== 1) {
                throw new RuntimeException('Status booking berubah selama pembayaran.');
            }
            $this->database->exec('COMMIT');
        } catch (Throwable $exception) {
            $this->database->exec('ROLLBACK');
            if ($exception instanceof SQLite3Exception) {
                return false;
            }
            throw $exception;
        }

        return true;
    }
}
