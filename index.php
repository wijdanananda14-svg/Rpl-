<?php

declare(strict_types=1);

session_start();

$databasePath = __DIR__ . '/storage/reservasi.sqlite';
$database = new SQLite3($databasePath);
$database->exec('PRAGMA foreign_keys = ON');
$database->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user'
);
CREATE TABLE IF NOT EXISTS fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    sport_type TEXT NOT NULL,
    price INTEGER NOT NULL,
    facilities TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    field_id INTEGER NOT NULL,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    booking_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Menunggu pembayaran',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id)
);
SQL);

$adminExists = (int) $database->querySingle("SELECT COUNT(*) FROM users WHERE role = 'admin'");
if ($adminExists === 0) {
    $admin = $database->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
    $admin->bindValue(':name', 'Administrator Sportiva', SQLITE3_TEXT);
    $admin->bindValue(':email', 'admin@sportiva.test', SQLITE3_TEXT);
    $admin->bindValue(':password', password_hash('admin123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $admin->bindValue(':role', 'admin', SQLITE3_TEXT);
    $admin->execute();
}

if ((int) $database->querySingle('SELECT COUNT(*) FROM fields') === 0) {
    $seed = $database->prepare('INSERT INTO fields (name, sport_type, price, facilities) VALUES (:name, :sport_type, :price, :facilities)');
    foreach ([
        ['Arena Futsal A', 'Futsal', 120000, 'Rumput sintetis, lampu, ruang ganti'],
        ['Court Badminton 1', 'Badminton', 60000, 'Lantai vinyl, lampu, tribun'],
        ['Court Basket Utama', 'Basket', 100000, 'Lantai kayu, ring standar, scoreboard'],
    ] as $field) {
        $seed->bindValue(':name', $field[0], SQLITE3_TEXT);
        $seed->bindValue(':sport_type', $field[1], SQLITE3_TEXT);
        $seed->bindValue(':price', $field[2], SQLITE3_INTEGER);
        $seed->bindValue(':facilities', $field[3], SQLITE3_TEXT);
        $seed->execute();
    }
}

$message = '';
$messageType = 'success';
$page = (string) ($_GET['page'] ?? 'home');
$action = (string) ($_GET['action'] ?? '');

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?page=admin');
    exit;
}

if (($action === 'login') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $login = $database->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
    $login->bindValue(':email', $email, SQLITE3_TEXT);
    $adminAccount = $login->execute()->fetchArray(SQLITE3_ASSOC);

    if ($adminAccount && password_verify($password, $adminAccount['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => $adminAccount['id'],
            'name' => $adminAccount['name'],
            'email' => $adminAccount['email'],
        ];
        header('Location: index.php?page=admin');
        exit;
    }

    $message = 'Email atau password admin salah.';
    $messageType = 'error';
}

if ($action === 'update_status' && isset($_SESSION['admin']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');
    $allowedStatuses = ['Menunggu pembayaran', 'Dikonfirmasi', 'Dibatalkan'];

    if ($bookingId && in_array($status, $allowedStatuses, true)) {
        $updateStatus = $database->prepare('UPDATE bookings SET status = :status WHERE id = :id');
        $updateStatus->bindValue(':status', $status, SQLITE3_TEXT);
        $updateStatus->bindValue(':id', $bookingId, SQLITE3_INTEGER);
        $updateStatus->execute();
    }

    header('Location: index.php?page=admin&updated=1');
    exit;
}

if ($action !== 'login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fieldId = filter_input(INPUT_POST, 'field_id', FILTER_VALIDATE_INT);
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
    $bookingDate = (string) ($_POST['booking_date'] ?? '');
    $startTime = (string) ($_POST['start_time'] ?? '');
    $endTime = (string) ($_POST['end_time'] ?? '');
    $dateIsValid = DateTime::createFromFormat('Y-m-d', $bookingDate) !== false;
    $timeIsValid = $startTime !== '' && $endTime !== '' && $startTime < $endTime;

    if (!$fieldId || $customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || !$dateIsValid || !$timeIsValid) {
        $message = 'Lengkapi data booking dengan benar.';
        $messageType = 'error';
    } else {
        $availability = $database->prepare(<<<SQL
            SELECT COUNT(*) FROM bookings
            WHERE field_id = :field_id AND booking_date = :booking_date
              AND status != 'Dibatalkan' AND start_time < :end_time AND end_time > :start_time
        SQL);
        $availability->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
        $availability->bindValue(':booking_date', $bookingDate, SQLITE3_TEXT);
        $availability->bindValue(':start_time', $startTime, SQLITE3_TEXT);
        $availability->bindValue(':end_time', $endTime, SQLITE3_TEXT);

        if ((int) $availability->execute()->fetchArray()[0] > 0) {
            $message = 'Jadwal tersebut sudah dipesan. Silakan pilih waktu lain.';
            $messageType = 'error';
        } else {
            $booking = $database->prepare(<<<SQL
                INSERT INTO bookings (field_id, customer_name, customer_email, booking_date, start_time, end_time)
                VALUES (:field_id, :customer_name, :customer_email, :booking_date, :start_time, :end_time)
            SQL);
            $booking->bindValue(':field_id', $fieldId, SQLITE3_INTEGER);
            $booking->bindValue(':customer_name', $customerName, SQLITE3_TEXT);
            $booking->bindValue(':customer_email', $customerEmail, SQLITE3_TEXT);
            $booking->bindValue(':booking_date', $bookingDate, SQLITE3_TEXT);
            $booking->bindValue(':start_time', $startTime, SQLITE3_TEXT);
            $booking->bindValue(':end_time', $endTime, SQLITE3_TEXT);
            $booking->execute();
            $message = 'Booking berhasil dibuat dengan status menunggu pembayaran.';
        }
    }
}

$fields = $database->query('SELECT * FROM fields WHERE is_active = 1 ORDER BY id');
$bookings = $database->query(<<<SQL
    SELECT bookings.*, fields.name AS field_name FROM bookings
    JOIN fields ON fields.id = bookings.field_id
    ORDER BY bookings.booking_date DESC, bookings.start_time DESC LIMIT 10
SQL);

$adminBookings = $database->query(<<<SQL
    SELECT bookings.*, fields.name AS field_name
    FROM bookings
    JOIN fields ON fields.id = bookings.field_id
    ORDER BY bookings.created_at DESC
SQL);

function formatRupiah(int $amount): string { return 'Rp ' . number_format($amount, 0, ',', '.'); }
function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportiva | Reservasi Lapangan</title>
    <link rel="stylesheet" href="public/css/style.css">
    <script src="public/js/app.js" defer></script>
</head>
<body>
<?php if ($page === 'admin'): ?>
    <main class="container admin-page">
        <?php if (isset($_SESSION['admin'])): ?>
            <section class="section-heading admin-heading">
                <div><p class="eyebrow">AREA ADMIN</p><h1>Dashboard admin</h1><p class="muted">Selamat datang, <?= escape((string) $_SESSION['admin']['name']) ?>.</p></div>
                <div class="admin-actions"><a class="button-link" href="index.php">Halaman utama</a><a class="button-link button-link-dark" href="index.php?action=logout">Logout</a></div>
            </section>
            <section class="history">
                <div class="section-heading"><div><p class="eyebrow">DATA MASUK</p><h2>Semua reservasi</h2></div></div>
                <?php if (isset($_GET['updated'])): ?><div class="message">Status reservasi berhasil diperbarui.</div><?php endif; ?>
                <div class="table-wrap"><table><thead><tr><th>Pemesan</th><th>Email</th><th>Lapangan</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php while ($booking = $adminBookings->fetchArray(SQLITE3_ASSOC)): ?><tr><td><?= escape($booking['customer_name']) ?></td><td><?= escape($booking['customer_email']) ?></td><td><?= escape($booking['field_name']) ?></td><td><?= escape($booking['booking_date']) ?>, <?= escape($booking['start_time']) ?> - <?= escape($booking['end_time']) ?></td><td><span class="status"><?= escape($booking['status']) ?></span></td><td><form class="status-form" method="post" action="index.php?page=admin&amp;action=update_status"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><select name="status"><option <?= $booking['status'] === 'Menunggu pembayaran' ? 'selected' : '' ?>>Menunggu pembayaran</option><option <?= $booking['status'] === 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option><option <?= $booking['status'] === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option></select><button type="submit">Simpan</button></form></td></tr><?php endwhile; ?>
                </tbody></table></div>
            </section>
        <?php else: ?>
            <section class="booking-layout login-panel">
                <div><p class="eyebrow">AKSES PENGELOLA</p><h1>Login admin</h1><p class="muted">Masuk untuk melihat seluruh data reservasi lapangan.</p></div>
                <?php if ($message !== ''): ?><div class="message <?= escape($messageType) ?>"><?= escape($message) ?></div><?php endif; ?>
                <form class="booking-form" method="post" action="index.php?page=admin&amp;action=login">
                    <label>Email admin<input type="email" name="email" placeholder="admin@sportiva.test" required></label>
                    <label>Password<input type="password" name="password" placeholder="admin123" required></label>
                    <button type="submit">Masuk ke dashboard</button>
                </form>
            </section>
        <?php endif; ?>
    </main>
<?php else: ?>
    <header class="hero">
        <nav class="navigation"><strong>SPORTIVA</strong><span>Reservasi lapangan olahraga</span><a href="index.php?page=admin">Login admin</a></nav>
        <div class="hero-content">
            <p class="eyebrow">SISTEM RESERVASI</p>
            <h1>Main lebih terencana.</h1>
            <p>Pilih lapangan, tentukan waktu, dan amankan jadwal bermainmu dalam satu tempat.</p>
        </div>
    </header>
    <main class="container">
        <?php if ($message !== ''): ?><div class="message <?= escape($messageType) ?>"><?= escape($message) ?></div><?php endif; ?>
        <section class="section-heading"><div><p class="eyebrow">PILIH TEMPAT BERMAIN</p><h2>Lapangan tersedia</h2></div><p>Harga tercantum per jam.</p></section>
        <section class="field-grid">
            <?php while ($field = $fields->fetchArray(SQLITE3_ASSOC)): ?>
                <article class="field-card"><div class="field-card-top"><span class="tag"><?= escape($field['sport_type']) ?></span><span class="field-number">0<?= (int) $field['id'] ?></span></div><h3><?= escape($field['name']) ?></h3><p><?= escape($field['facilities']) ?></p><strong class="price"><?= formatRupiah((int) $field['price']) ?> <small>/ jam</small></strong></article>
            <?php endwhile; ?>
        </section>
        <section class="booking-layout">
            <div><p class="eyebrow">BOOKING BARU</p><h2>Pesan lapangan</h2><p class="muted">Isi data berikut untuk membuat reservasi. Pembayaran masih berupa simulasi.</p></div>
            <form class="booking-form" method="post">
                <label>Lapangan<select name="field_id" required><option value="">Pilih lapangan</option><?php $formFields = $database->query('SELECT id, name, sport_type FROM fields WHERE is_active = 1 ORDER BY id'); while ($field = $formFields->fetchArray(SQLITE3_ASSOC)): ?><option value="<?= (int) $field['id'] ?>"><?= escape($field['name']) ?> - <?= escape($field['sport_type']) ?></option><?php endwhile; ?></select></label>
                <label>Nama pemesan<input type="text" name="customer_name" required></label>
                <label>Email<input type="email" name="customer_email" required></label>
                <label>Tanggal<input type="date" name="booking_date" min="<?= date('Y-m-d') ?>" required></label>
                <div class="time-row"><label>Mulai<input type="time" name="start_time" required></label><label>Selesai<input type="time" name="end_time" required></label></div>
                <button type="submit">Buat reservasi</button>
            </form>
        </section>
        <section class="history"><div class="section-heading"><div><p class="eyebrow">AKTIVITAS TERBARU</p><h2>Riwayat reservasi</h2></div></div><div class="table-wrap"><table><thead><tr><th>Pemesan</th><th>Lapangan</th><th>Jadwal</th><th>Status</th></tr></thead><tbody>
            <?php while ($booking = $bookings->fetchArray(SQLITE3_ASSOC)): ?><tr><td><?= escape($booking['customer_name']) ?></td><td><?= escape($booking['field_name']) ?></td><td><?= escape($booking['booking_date']) ?>, <?= escape($booking['start_time']) ?> - <?= escape($booking['end_time']) ?></td><td><span class="status"><?= escape($booking['status']) ?></span></td></tr><?php endwhile; ?>
        </tbody></table></div></section>
    </main>
<?php endif; ?>
</body>
</html>
