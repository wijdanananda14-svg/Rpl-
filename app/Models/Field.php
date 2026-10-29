<?php

declare(strict_types=1);

final class Field
{
    public function __construct(private readonly SQLite3 $database)
    {
    }

    public function allActive(): array
    {
        $result = $this->database->query('SELECT * FROM fields WHERE is_active = 1 ORDER BY id');
        $fields = [];

        while ($field = $result->fetchArray(SQLITE3_ASSOC)) {
            $fields[] = $field;
        }

        return $fields;
    }

    public function all(): array
    {
        $result = $this->database->query('SELECT * FROM fields ORDER BY id');
        $fields = [];
        while ($field = $result->fetchArray(SQLITE3_ASSOC)) {
            $fields[] = $field;
        }
        return $fields;
    }

    public function save(?int $id, string $name, string $sportType, int $price, string $facilities): bool
    {
        if (trim($name) === '' || trim($sportType) === '' || $price < 1) {
            return false;
        }

        if ($id === null) {
            $statement = $this->database->prepare('INSERT INTO fields (name, sport_type, price, facilities) VALUES (:name, :sport_type, :price, :facilities)');
        } else {
            $exists = $this->database->prepare('SELECT 1 FROM fields WHERE id = :id');
            $exists->bindValue(':id', $id, SQLITE3_INTEGER);
            if ($exists->execute()->fetchArray() === false) {
                return false;
            }
            $statement = $this->database->prepare('UPDATE fields SET name = :name, sport_type = :sport_type, price = :price, facilities = :facilities WHERE id = :id');
            $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $statement->bindValue(':name', trim($name), SQLITE3_TEXT);
        $statement->bindValue(':sport_type', trim($sportType), SQLITE3_TEXT);
        $statement->bindValue(':price', $price, SQLITE3_INTEGER);
        $statement->bindValue(':facilities', trim($facilities), SQLITE3_TEXT);
        if ($statement->execute() === false) {
            throw new RuntimeException('Data lapangan gagal disimpan.');
        }
        return true;
    }

    public function setActive(int $id, bool $isActive): bool
    {
        $statement = $this->database->prepare('UPDATE fields SET is_active = :is_active WHERE id = :id');
        $statement->bindValue(':is_active', $isActive ? 1 : 0, SQLITE3_INTEGER);
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        if ($statement->execute() === false) {
            throw new RuntimeException('Status lapangan gagal diperbarui.');
        }
        return $this->database->changes() === 1;
    }
}
