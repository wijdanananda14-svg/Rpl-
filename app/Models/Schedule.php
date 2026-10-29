<?php

declare(strict_types=1);

final class Schedule
{
    public function __construct(private readonly SQLite3 $database)
    {
    }

    public function all(): array
    {
        $result = $this->database->query(<<<SQL
            SELECT schedules.*, fields.name AS field_name
            FROM schedules JOIN fields ON fields.id = schedules.field_id
            ORDER BY schedules.schedule_date, schedules.start_time
        SQL);
        $schedules = [];
        while ($schedule = $result->fetchArray(SQLITE3_ASSOC)) {
            $schedules[] = $schedule;
        }
        return $schedules;
    }

    public function create(int $fieldId, string $date, string $start, string $end): bool
    {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
        if ($fieldId < 1 || $parsedDate === false || $parsedDate->format('Y-m-d') !== $date || $date < date('Y-m-d')
            || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) !== 1
            || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end) !== 1 || $start >= $end) {
            return false;
        }

        $field = $this->database->prepare('SELECT id FROM fields WHERE id = :id AND is_active = 1');
        $field->bindValue(':id', $fieldId, SQLITE3_INTEGER);
        if ($field->execute()->fetchArray() === false) {
            return false;
        }

        $overlap = $this->database->prepare(<<<SQL
            SELECT COUNT(*) FROM schedules
            WHERE field_id = :field_id AND schedule_date = :schedule_date AND status = 'Tersedia'
              AND start_time < :end_time AND end_time > :start_time
        SQL);
        $overlap->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $overlap->bindValue(':schedule_date', $date, SQLITE3_TEXT);
        $overlap->bindValue(':start_time', $start, SQLITE3_TEXT);
        $overlap->bindValue(':end_time', $end, SQLITE3_TEXT);
        if ((int) $overlap->execute()->fetchArray()[0] > 0) {
            return false;
        }

        $statement = $this->database->prepare("INSERT INTO schedules (field_id, schedule_date, start_time, end_time, status) VALUES (:field_id, :schedule_date, :start_time, :end_time, 'Tersedia')");
        $statement->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $statement->bindValue(':schedule_date', $date, SQLITE3_TEXT);
        $statement->bindValue(':start_time', $start, SQLITE3_TEXT);
        $statement->bindValue(':end_time', $end, SQLITE3_TEXT);
        return $statement->execute() !== false;
    }

    public function setStatus(int $id, string $status): bool
    {
        if ($id < 1 || !in_array($status, ['Tersedia', 'Ditutup'], true)) {
            return false;
        }
        $statement = $this->database->prepare('UPDATE schedules SET status = :status WHERE id = :id');
        $statement->bindValue(':status', $status, SQLITE3_TEXT);
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        if ($statement->execute() === false) {
            throw new RuntimeException('Status jadwal gagal diperbarui.');
        }
        return $this->database->changes() === 1;
    }
}
