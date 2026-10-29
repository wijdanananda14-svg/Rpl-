<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Config/Database.php';
require_once __DIR__ . '/app/Controllers/BookingController.php';
require_once __DIR__ . '/app/Models/Account.php';
require_once __DIR__ . '/app/Models/Booking.php';
require_once __DIR__ . '/app/Models/Field.php';
require_once __DIR__ . '/app/Models/Payment.php';
require_once __DIR__ . '/app/Models/Schedule.php';

session_start();

$database = Database::connection();
$accountModel = new Account($database);
$bookingController = new BookingController();
$bookingModel = new Booking($database);
$fieldModel = new Field($database);
$paymentModel = new Payment($database);
$scheduleModel = new Schedule($database);

if ((int) $database->querySingle("SELECT COUNT(*) FROM users WHERE role = 'admin'") === 0) {
    $statement = $database->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
    $statement->bindValue(':name', 'Administrator Sportiva', SQLITE3_TEXT);
    $statement->bindValue(':email', 'admin@sportiva.test', SQLITE3_TEXT);
    $statement->bindValue(':password', password_hash('admin123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $statement->bindValue(':role', 'admin', SQLITE3_TEXT);
    $statement->execute();
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

$page = (string) ($_GET['page'] ?? 'home');
$action = (string) ($_GET['action'] ?? '');
$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    switch ($action) {
        case 'register':
            $result = $accountModel->register(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );
            if ($result['success']) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => $result['id'],
                    'name' => trim((string) $_POST['name']),
                    'email' => strtolower(trim((string) $_POST['email'])),
                ];
                header('Location: index.php?page=dashboard&registered=1');
                exit;
            }
            $page = 'register';
            $message = $result['message'];
            $messageType = 'error';
            break;

        case 'login':
        case 'admin_login':
            $role = $action === 'admin_login' ? 'admin' : 'user';
            $authenticated = $accountModel->authenticate(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                $role
            );
            if ($authenticated !== null) {
                session_regenerate_id(true);
                $_SESSION[$role] = $authenticated;
                header('Location: index.php?page=' . ($role === 'admin' ? 'admin' : 'dashboard'));
                exit;
            }
            $page = $role === 'admin' ? 'admin' : 'login';
            $message = 'Email atau password salah.';
            $messageType = 'error';
            break;

        case 'update_profile':
            if (!isset($_SESSION['user'])) {
                header('Location: index.php?page=login');
                exit;
            }
            $profile = $accountModel->updateProfile(
                (int) $_SESSION['user']['id'],
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? '')
            );
            if ($profile['success']) {
                $_SESSION['user']['name'] = $profile['name'];
                $_SESSION['user']['email'] = $profile['email'];
                header('Location: index.php?page=dashboard&profile_updated=1');
                exit;
            }
            $message = $profile['message'];
            $messageType = 'error';
            $page = 'dashboard';
            break;

        case 'create_booking':
            if (!isset($_SESSION['user'])) {
                header('Location: index.php?page=login&required=1');
                exit;
            }
            $bookingInput = $_POST;
            $bookingInput['customer_name'] = $_SESSION['user']['name'];
            $bookingInput['customer_email'] = $_SESSION['user']['email'];
            $validation = $bookingController->validate($bookingInput);
            if (!$validation['valid']) {
                $message = $validation['message'];
                $messageType = 'error';
            } else {
                $user = $_SESSION['user'];
                $result = $bookingModel->create(
                    (int) $_POST['field_id'],
                    (string) $user['name'],
                    (string) $user['email'],
                    (string) $_POST['booking_date'],
                    (string) $_POST['start_time'],
                    (string) $_POST['end_time'],
                    (int) $user['id']
                );
                $messages = [
                    'field_unavailable' => ['Lapangan yang dipilih tidak tersedia.', 'error'],
                    'outside_schedule' => ['Waktu harus berada dalam jadwal yang dibuka admin.', 'error'],
                    'time_unavailable' => ['Jadwal tersebut sudah dipesan. Silakan pilih waktu lain.', 'error'],
                    'created' => ['Booking berhasil dibuat. Silakan selesaikan pembayaran simulasi dari dashboard.', 'success'],
                ];
                [$message, $messageType] = $messages[$result] ?? ['Booking gagal dibuat.', 'error'];
            }
            $page = 'home';
            break;

        case 'pay':
            if (!isset($_SESSION['user'])) {
                header('Location: index.php?page=login&required=1');
                exit;
            }
            $paid = $paymentModel->simulate(
                filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                (int) $_SESSION['user']['id'],
                (string) ($_POST['method'] ?? '')
            );
            header('Location: index.php?page=dashboard&' . ($paid ? 'paid=1' : 'payment_error=1'));
            exit;

        case 'cancel_booking':
            if (!isset($_SESSION['user'])) {
                header('Location: index.php?page=login&required=1');
                exit;
            }
            $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
            $cancelled = $bookingModel->cancel($bookingId, (int) $_SESSION['user']['id']);
            header('Location: index.php?page=dashboard&' . ($cancelled ? 'cancelled=1' : 'cancel_error=1'));
            exit;

        case 'update_status':
            if (!isset($_SESSION['admin'])) {
                header('Location: index.php?page=admin');
                exit;
            }
            $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
            $updated = $bookingModel->updateStatus($bookingId, (string) ($_POST['status'] ?? ''));
            header('Location: index.php?page=admin&' . ($updated ? 'updated=1' : 'update_error=1'));
            exit;

        case 'save_field':
            if (!isset($_SESSION['admin'])) {
                header('Location: index.php?page=admin');
                exit;
            }
            $rawId = filter_var($_POST['field_id'] ?? '', FILTER_VALIDATE_INT);
            $id = $rawId === false || $rawId === 0 ? null : $rawId;
            $saved = $fieldModel->save(
                $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['sport_type'] ?? ''),
                filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                (string) ($_POST['facilities'] ?? '')
            );
            header('Location: index.php?page=admin&' . ($saved ? 'field_saved=1' : 'field_error=1'));
            exit;

        case 'toggle_field':
            if (!isset($_SESSION['admin'])) {
                header('Location: index.php?page=admin');
                exit;
            }
            $fieldId = filter_var($_POST['field_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
            $isActive = ($_POST['is_active'] ?? '') === '1';
            $changed = $fieldId > 0 && $fieldModel->setActive($fieldId, $isActive);
            header('Location: index.php?page=admin&' . ($changed ? 'field_saved=1' : 'field_error=1'));
            exit;

        case 'save_schedule':
            if (!isset($_SESSION['admin'])) {
                header('Location: index.php?page=admin');
                exit;
            }
            $scheduleId = filter_var($_POST['schedule_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
            if ($scheduleId > 0) {
                $scheduleSaved = $scheduleModel->setStatus($scheduleId, (string) ($_POST['status'] ?? ''));
            } else {
                $scheduleSaved = $scheduleModel->create(
                    filter_var($_POST['field_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    (string) ($_POST['schedule_date'] ?? ''),
                    (string) ($_POST['start_time'] ?? ''),
                    (string) ($_POST['end_time'] ?? '')
                );
            }
            header('Location: index.php?page=admin&' . ($scheduleSaved ? 'schedule_saved=1' : 'schedule_error=1'));
            exit;
    }
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_GET['registered'])) {
    $message = 'Akun berhasil dibuat. Kamu sudah masuk.';
}
if (isset($_GET['paid'])) {
    $message = 'Pembayaran simulasi berhasil. Booking dikonfirmasi.';
}
if (isset($_GET['profile_updated'])) {
    $message = 'Profil berhasil diperbarui.';
}
if (isset($_GET['payment_error'])) {
    $message = 'Pembayaran gagal atau booking bukan milik akun ini.';
    $messageType = 'error';
}
if (isset($_GET['cancelled'])) {
    $message = 'Booking berhasil dibatalkan.';
}
if (isset($_GET['cancel_error'])) {
    $message = 'Booking tidak ditemukan atau tidak dapat dibatalkan.';
    $messageType = 'error';
}
if (isset($_GET['updated'])) {
    $message = 'Status booking diperbarui.';
}
if (isset($_GET['update_error']) || isset($_GET['field_error']) || isset($_GET['schedule_error'])) {
    $message = 'Perubahan tidak valid atau data tidak ditemukan.';
    $messageType = 'error';
}
if (isset($_GET['field_saved']) || isset($_GET['schedule_saved'])) {
    $message = 'Data berhasil disimpan.';
}

$activeFields = $fieldModel->allActive();
$allFields = isset($_SESSION['admin']) ? $fieldModel->all() : [];
$upcomingSchedules = $database->query(<<<SQL
    SELECT schedules.*, fields.name AS field_name
    FROM schedules JOIN fields ON fields.id = schedules.field_id
    WHERE schedules.status = 'Tersedia' AND schedules.schedule_date >= date('now', 'localtime')
    ORDER BY schedules.schedule_date, schedules.start_time LIMIT 20
SQL);
$userBookings = isset($_SESSION['user']) ? $bookingModel->forUser((int) $_SESSION['user']['id']) : null;
$adminBookings = isset($_SESSION['admin']) ? $database->query(<<<SQL
    SELECT bookings.*, fields.name AS field_name,
           payments.status AS payment_status, payments.amount AS payment_amount
    FROM bookings JOIN fields ON fields.id = bookings.field_id
    LEFT JOIN payments ON payments.booking_id = bookings.id
    ORDER BY bookings.created_at DESC
SQL) : null;
$adminSchedules = isset($_SESSION['admin']) ? $scheduleModel->all() : [];
$report = isset($_SESSION['admin']) ? [
    'bookings' => (int) $database->querySingle('SELECT COUNT(*) FROM bookings'),
    'pending' => (int) $database->querySingle("SELECT COUNT(*) FROM bookings WHERE status = 'Menunggu pembayaran'"),
    'confirmed' => (int) $database->querySingle("SELECT COUNT(*) FROM bookings WHERE status = 'Dikonfirmasi'"),
    'revenue' => (int) $database->querySingle("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Lunas'"),
] : [];

function formatRupiah(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function escape(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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
                <div><p class="eyebrow">AREA ADMIN</p><h1>Dashboard admin</h1><p class="muted">Selamat datang, <?= escape($_SESSION['admin']['name']) ?>.</p></div>
                <div class="admin-actions"><a class="button-link" href="index.php">Halaman utama</a><a class="button-link button-link-dark" href="index.php?action=logout">Logout</a></div>
            </section>
            <?php if ($message !== ''): ?><div class="message <?= escape($messageType) ?>"><?= escape($message) ?></div><?php endif; ?>
            <section class="field-grid report-grid">
                <article class="field-card"><p>Total booking</p><strong class="price"><?= $report['bookings'] ?></strong></article>
                <article class="field-card"><p>Menunggu pembayaran</p><strong class="price"><?= $report['pending'] ?></strong></article>
                <article class="field-card"><p>Pendapatan simulasi</p><strong class="price"><?= formatRupiah($report['revenue']) ?></strong></article>
            </section>
            <section class="history">
                <div class="section-heading"><div><p class="eyebrow">DATA MASUK</p><h2>Semua reservasi</h2></div></div>
                <div class="table-wrap"><table><thead><tr><th>Pemesan</th><th>Email</th><th>Lapangan</th><th>Jadwal</th><th>Pembayaran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php $adminBookingCount = 0; while ($booking = $adminBookings->fetchArray(SQLITE3_ASSOC)): $adminBookingCount++; ?>
                        <tr><td><?= escape($booking['customer_name']) ?></td><td><?= escape($booking['customer_email']) ?></td><td><?= escape($booking['field_name']) ?></td><td><?= escape($booking['booking_date']) ?>, <?= escape($booking['start_time']) ?>–<?= escape($booking['end_time']) ?></td><td><?= escape($booking['payment_status'] ?? 'Belum dibayar') ?><?= $booking['payment_amount'] !== null ? ' · ' . escape(formatRupiah((int) $booking['payment_amount'])) : '' ?></td><td><span class="status"><?= escape($booking['status']) ?></span></td><td><form class="status-form" method="post" action="index.php?page=admin&amp;action=update_status"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><select name="status"><?php foreach (Booking::STATUSES as $statusOption): ?><option value="<?= escape($statusOption) ?>" <?= $booking['status'] === $statusOption ? 'selected' : '' ?>><?= escape($statusOption) ?></option><?php endforeach; ?></select><button type="submit">Simpan</button></form></td></tr>
                    <?php endwhile; ?>
                    <?php if ($adminBookingCount === 0): ?><tr><td colspan="7">Belum ada reservasi.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
            <section class="booking-layout admin-editor">
                <div><p class="eyebrow">KATALOG</p><h2>Kelola lapangan</h2></div>
                <div>
                    <form class="booking-form" method="post" action="index.php?page=admin&amp;action=save_field">
                        <input type="hidden" name="field_id" value="">
                        <label>Nama lapangan<input name="name" required></label>
                        <label>Jenis olahraga<input name="sport_type" required></label>
                        <label>Harga per jam<input type="number" name="price" min="1" required></label>
                        <label>Fasilitas<input name="facilities"></label>
                        <button type="submit">Tambah lapangan</button>
                    </form>
                    <?php foreach ($allFields as $field): ?>
                        <div class="field-admin-row">
                            <form class="booking-form" method="post" action="index.php?page=admin&amp;action=save_field">
                                <input type="hidden" name="field_id" value="<?= (int) $field['id'] ?>">
                                <label>Nama<input name="name" value="<?= escape($field['name']) ?>" required></label>
                                <label>Jenis<input name="sport_type" value="<?= escape($field['sport_type']) ?>" required></label>
                                <label>Harga<input type="number" name="price" min="1" value="<?= (int) $field['price'] ?>" required></label>
                                <label>Fasilitas<input name="facilities" value="<?= escape($field['facilities']) ?>"></label>
                                <button type="submit">Simpan lapangan</button>
                            </form>
                            <form method="post" action="index.php?page=admin&amp;action=toggle_field">
                                <input type="hidden" name="field_id" value="<?= (int) $field['id'] ?>">
                                <input type="hidden" name="is_active" value="<?= $field['is_active'] ? '0' : '1' ?>">
                                <button type="submit"><?= $field['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="booking-layout admin-editor">
                <div><p class="eyebrow">JADWAL TERSEDIA</p><h2>Kelola jadwal</h2><p class="muted">Jika jadwal tersedia telah ditambahkan untuk lapangan dan tanggal tertentu, booking harus berada di dalam jadwal tersebut.</p></div>
                <div>
                    <form class="booking-form" method="post" action="index.php?page=admin&amp;action=save_schedule">
                        <label>Lapangan<select name="field_id" required><?php foreach ($activeFields as $field): ?><option value="<?= (int) $field['id'] ?>"><?= escape($field['name']) ?></option><?php endforeach; ?></select></label>
                        <label>Tanggal<input type="date" name="schedule_date" min="<?= date('Y-m-d') ?>" required></label>
                        <label>Mulai<input type="time" name="start_time" required></label>
                        <label>Selesai<input type="time" name="end_time" required></label>
                        <button type="submit">Tambah jadwal</button>
                    </form>
                    <div class="table-wrap"><table><thead><tr><th>Lapangan</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                        <?php foreach ($adminSchedules as $schedule): ?>
                            <tr><td><?= escape($schedule['field_name']) ?></td><td><?= escape($schedule['schedule_date']) ?></td><td><?= escape($schedule['start_time']) ?>–<?= escape($schedule['end_time']) ?></td><td><?= escape($schedule['status']) ?></td><td><form method="post" action="index.php?page=admin&amp;action=save_schedule"><input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>"><input type="hidden" name="status" value="<?= $schedule['status'] === 'Tersedia' ? 'Ditutup' : 'Tersedia' ?>"><button type="submit"><?= $schedule['status'] === 'Tersedia' ? 'Tutup' : 'Buka' ?></button></form></td></tr>
                        <?php endforeach; ?>
                        <?php if ($adminSchedules === []): ?><tr><td colspan="5">Belum ada jadwal yang dikonfigurasi.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </div>
            </section>
        <?php else: ?>
            <section class="booking-layout login-panel">
                <div><p class="eyebrow">AKSES PENGELOLA</p><h1>Login admin</h1><p class="muted">Masuk untuk mengelola lapangan, jadwal, reservasi, dan laporan.</p></div>
                <?php if ($message !== ''): ?><div class="message error"><?= escape($message) ?></div><?php endif; ?>
                <form class="booking-form" method="post" action="index.php?page=admin&amp;action=admin_login">
                    <label>Email admin<input type="email" name="email" required></label>
                    <label>Password<input type="password" name="password" required></label>
                    <button type="submit">Masuk ke dashboard</button>
                </form>
            </section>
        <?php endif; ?>
    </main>
<?php elseif ($page === 'register' || $page === 'login'): ?>
    <main class="container admin-page">
        <section class="booking-layout login-panel">
            <div><p class="eyebrow">AKUN PELANGGAN</p><h1><?= $page === 'register' ? 'Buat akun' : 'Login pengguna' ?></h1><p class="muted">Akun diperlukan untuk menyimpan booking, membayar simulasi, dan melihat riwayat.</p></div>
            <?php if (isset($_GET['required'])): ?><div class="message">Login atau daftar terlebih dahulu untuk memesan.</div><?php endif; ?>
            <?php if ($message !== ''): ?><div class="message <?= escape($messageType) ?>"><?= escape($message) ?></div><?php endif; ?>
            <form class="booking-form" method="post" action="index.php?page=<?= $page ?>&amp;action=<?= $page ?>">
                <?php if ($page === 'register'): ?><label>Nama<input name="name" required maxlength="100"></label><?php endif; ?>
                <label>Email<input type="email" name="email" required></label>
                <label>Password<input type="password" name="password" minlength="8" required></label>
                <button type="submit"><?= $page === 'register' ? 'Daftar' : 'Masuk' ?></button>
                <p><?= $page === 'register' ? 'Sudah punya akun?' : 'Belum punya akun?' ?> <a href="index.php?page=<?= $page === 'register' ? 'login' : 'register' ?>"><?= $page === 'register' ? 'Login' : 'Daftar' ?></a></p>
            </form>
        </section>
    </main>
<?php elseif ($page === 'dashboard'): ?>
    <main class="container admin-page">
        <section class="section-heading admin-heading">
            <div><p class="eyebrow">AKUN PENGGUNA</p><h1>Dashboard saya</h1><p class="muted">Halo, <?= escape($_SESSION['user']['name'] ?? '') ?>.</p></div>
            <div class="admin-actions"><a class="button-link" href="index.php">Pesan lapangan</a><a class="button-link button-link-dark" href="index.php?action=logout">Logout</a></div>
        </section>
        <?php if ($message !== ''): ?><div class="message <?= escape($messageType) ?>"><?= escape($message) ?></div><?php endif; ?>
        <?php if (!isset($_SESSION['user'])): ?>
            <div class="message error">Silakan login untuk melihat dashboard.</div>
            <a class="button-link" href="index.php?page=login">Login</a>
        <?php else: ?>
            <section class="booking-layout admin-editor">
                <div><p class="eyebrow">PROFIL</p><h2>Informasi akun</h2></div>
                <form class="booking-form" method="post" action="index.php?page=dashboard&amp;action=update_profile">
                    <label>Nama<input name="name" value="<?= escape($_SESSION['user']['name']) ?>" required maxlength="100"></label>
                    <label>Email<input type="email" name="email" value="<?= escape($_SESSION['user']['email']) ?>" required></label>
                    <button type="submit">Simpan profil</button>
                </form>
            </section>
            <section class="history"><div class="section-heading"><div><p class="eyebrow">RESERVASI SAYA</p><h2>Riwayat booking</h2></div></div>
                <div class="table-wrap"><table><thead><tr><th>Lapangan</th><th>Jadwal</th><th>Status</th><th>Pembayaran</th><th>Aksi</th></tr></thead><tbody>
                    <?php $bookingCount = 0; while ($booking = $userBookings->fetchArray(SQLITE3_ASSOC)): $bookingCount++; ?>
                        <tr><td><?= escape($booking['field_name']) ?></td><td><?= escape($booking['booking_date']) ?>, <?= escape($booking['start_time']) ?>–<?= escape($booking['end_time']) ?></td><td><span class="status"><?= escape($booking['status']) ?></span></td><td><?= escape($booking['payment_status'] ?? 'Belum dibayar') ?><?= $booking['payment_amount'] !== null ? ' · ' . escape(formatRupiah((int) $booking['payment_amount'])) : '' ?></td><td>
                            <?php if ($booking['status'] === 'Menunggu pembayaran'): ?>
                                <form class="status-form" method="post" action="index.php?page=dashboard&amp;action=pay"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><select name="method"><option>Transfer simulasi</option><option>E-wallet simulasi</option></select><button type="submit">Bayar simulasi</button></form>
                                <form method="post" action="index.php?page=dashboard&amp;action=cancel_booking"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><button type="submit" class="button-secondary">Batalkan</button></form>
                            <?php else: ?>—<?php endif; ?>
                        </td></tr>
                    <?php endwhile; ?>
                    <?php if ($bookingCount === 0): ?><tr><td colspan="5">Belum ada reservasi. Pilih lapangan untuk membuat booking pertama.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
        <?php endif; ?>
    </main>
<?php else: ?>
    <header class="hero">
        <nav class="navigation"><strong>SPORTIVA</strong><span>Reservasi lapangan olahraga</span><?php if (isset($_SESSION['user'])): ?><a href="index.php?page=dashboard">Dashboard saya</a><?php else: ?><a href="index.php?page=login">Login / Daftar</a><?php endif; ?><a href="index.php?page=admin">Login admin</a></nav>
        <div class="hero-content"><p class="eyebrow">SISTEM RESERVASI</p><h1>Main lebih terencana.</h1><p>Pilih lapangan, tentukan waktu, dan amankan jadwal bermainmu dalam satu tempat.</p></div>
    </header>
    <main class="container">
        <?php if ($message !== ''): ?><div class="message <?= escape($messageType) ?>"><?= escape($message) ?></div><?php endif; ?>
        <section class="section-heading"><div><p class="eyebrow">PILIH TEMPAT BERMAIN</p><h2>Lapangan tersedia</h2></div><p>Harga tercantum per jam.</p></section>
        <?php if ($activeFields === []): ?><div class="message">Belum ada lapangan aktif. Silakan hubungi admin.</div><?php else: ?>
            <section class="field-grid">
                <?php foreach ($activeFields as $field): ?>
                    <article class="field-card"><div class="field-card-top"><span class="tag"><?= escape($field['sport_type']) ?></span><span class="field-number">0<?= (int) $field['id'] ?></span></div><h3><?= escape($field['name']) ?></h3><p><?= escape($field['facilities']) ?></p><strong class="price"><?= formatRupiah((int) $field['price']) ?> <small>/ jam</small></strong></article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
        <section class="history"><div class="section-heading"><div><p class="eyebrow">JADWAL DIBUKA</p><h2>Jadwal tersedia</h2></div></div>
            <div class="table-wrap"><table><thead><tr><th>Lapangan</th><th>Tanggal</th><th>Waktu</th></tr></thead><tbody>
                <?php $scheduleCount = 0; while ($schedule = $upcomingSchedules->fetchArray(SQLITE3_ASSOC)): $scheduleCount++; ?><tr><td><?= escape($schedule['field_name']) ?></td><td><?= escape($schedule['schedule_date']) ?></td><td><?= escape($schedule['start_time']) ?>–<?= escape($schedule['end_time']) ?></td></tr><?php endwhile; ?>
                <?php if ($scheduleCount === 0): ?><tr><td colspan="3">Belum ada jadwal khusus. Silakan tanyakan ketersediaan melalui form booking.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
        <section class="booking-layout">
            <div><p class="eyebrow">BOOKING BARU</p><h2>Pesan lapangan</h2><p class="muted"><?= isset($_SESSION['user']) ? 'Pembayaran simulasi tersedia setelah booking dibuat.' : 'Login atau daftar agar booking tersimpan pada akun dan dapat dibayar.' ?></p></div>
            <?php if (!isset($_SESSION['user'])): ?><div class="booking-form"><a class="button-link" href="index.php?page=login">Login untuk booking</a><a class="button-link" href="index.php?page=register">Buat akun</a></div><?php else: ?>
                <form class="booking-form" method="post" action="index.php?action=create_booking">
                    <label>Lapangan<select name="field_id" required><option value="">Pilih lapangan</option><?php foreach ($activeFields as $field): ?><option value="<?= (int) $field['id'] ?>"><?= escape($field['name']) ?> — <?= escape($field['sport_type']) ?></option><?php endforeach; ?></select></label>
                    <label>Tanggal<input type="date" name="booking_date" min="<?= date('Y-m-d') ?>" required></label>
                    <div class="time-row"><label>Mulai<input type="time" name="start_time" required></label><label>Selesai<input type="time" name="end_time" required></label></div>
                    <button type="submit">Buat reservasi</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
<?php endif; ?>
</body>
</html>
