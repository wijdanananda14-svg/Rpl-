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
}
