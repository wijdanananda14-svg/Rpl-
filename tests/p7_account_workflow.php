<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Models/Account.php';

$database = new SQLite3(':memory:');
$database->enableExceptions(true);
$database->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, role TEXT NOT NULL)');
$accounts = new Account($database);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
};

$assert(!$accounts->register('', 'u@example.com', 'password123')['success'], 'nama kosong harus ditolak');
$assert(!$accounts->register('Uji', 'invalid', 'password123')['success'], 'email tidak valid harus ditolak');
$assert(!$accounts->register('Uji', 'u@example.com', 'short')['success'], 'password pendek harus ditolak');
$registered = $accounts->register('Pengguna', 'U@example.com', 'password123');
$assert($registered['success'], 'akun dengan data valid harus dibuat');
$assert(!$accounts->register('Duplikat', 'u@example.com', 'password456')['success'], 'email duplikat harus ditolak');
$assert($accounts->authenticate('u@example.com', 'password123', 'user') !== null, 'email case-insensitive dan password benar harus bisa login');
$assert($accounts->authenticate('u@example.com', 'wrongpass', 'user') === null, 'password salah harus ditolak');
$assert($accounts->authenticate('u@example.com', 'password123', 'admin') === null, 'akun user tidak boleh login sebagai admin');
$assert((int) $database->querySingle("SELECT COUNT(*) FROM users WHERE password = 'password123'") === 0, 'password harus tersimpan dalam bentuk hash');
$other = $accounts->register('Akun Lain', 'lain@example.com', 'another-pass');
$assert($other['success'], 'akun kedua dapat dibuat untuk uji kepemilikan email');
$profile = $accounts->updateProfile((int) $registered['id'], 'Nama Baru', 'baru@example.com');
$assert($profile['success'], 'profil harus dapat diperbarui');
$assert($accounts->authenticate('baru@example.com', 'password123', 'user') !== null, 'email baru harus dapat digunakan login');
$assert(
    !$accounts->updateProfile((int) $registered['id'], 'Duplikat', 'lain@example.com')['success'],
    'profil tidak boleh menggunakan email akun lain'
);

echo "P7 akun lulus: registrasi, validasi, duplikasi, hash password, dan autentikasi/role.\n";
