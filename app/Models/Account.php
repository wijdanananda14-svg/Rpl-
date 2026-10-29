<?php

declare(strict_types=1);

final class Account
{
    public function __construct(private readonly SQLite3 $database)
    {
    }

    public function register(string $name, string $email, string $password): array
    {
        if (trim($name) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return ['success' => false, 'message' => 'Isi nama, email valid, dan password minimal 8 karakter.'];
        }

        $statement = $this->database->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $statement->bindValue(':name', trim($name), SQLITE3_TEXT);
        $statement->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
        $statement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        $statement->bindValue(':role', 'user', SQLITE3_TEXT);

        try {
            if ($statement->execute() === false) {
                throw new RuntimeException('Akun gagal disimpan.');
            }
        } catch (SQLite3Exception $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return ['success' => false, 'message' => 'Email sudah terdaftar.'];
            }
            throw $exception;
        }

        return ['success' => true, 'id' => $this->database->lastInsertRowID()];
    }

    public function authenticate(string $email, string $password, string $role): ?array
    {
        $statement = $this->database->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email AND role = :role LIMIT 1');
        $statement->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
        $statement->bindValue(':role', $role, SQLITE3_TEXT);
        $account = $statement->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$account || !password_verify($password, $account['password'])) {
            return null;
        }

        unset($account['password']);
        return $account;
    }

    public function updateProfile(int $userId, string $name, string $email): array
    {
        if ($userId < 1 || trim($name) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Nama dan email valid wajib diisi.'];
        }

        $statement = $this->database->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id AND role = :role');
        $statement->bindValue(':name', trim($name), SQLITE3_TEXT);
        $statement->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
        $statement->bindValue(':id', $userId, SQLITE3_INTEGER);
        $statement->bindValue(':role', 'user', SQLITE3_TEXT);
        try {
            if ($statement->execute() === false) {
                throw new RuntimeException('Profil gagal diperbarui.');
            }
        } catch (SQLite3Exception $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return ['success' => false, 'message' => 'Email sudah digunakan akun lain.'];
            }
            throw $exception;
        }

        return ['success' => true, 'name' => trim($name), 'email' => strtolower(trim($email))];
    }
}
