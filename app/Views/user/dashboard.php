<?php $title = 'Dashboard Pengguna | Sportiva'; require __DIR__ . '/../layouts/header.php'; ?>
<main class="container">
    <section class="section-heading">
        <div><p class="eyebrow">AKUN PENGGUNA</p><h1>Dashboard saya</h1></div>
        <p>Kelola reservasi dan lihat jadwal bermain.</p>
    </section>
    <section class="field-grid">
        <article class="field-card"><h2>Reservasi aktif</h2><p>Daftar booking yang masih menunggu pembayaran atau sudah terkonfirmasi.</p></article>
        <article class="field-card"><h2>Riwayat</h2><p>Semua reservasi yang pernah dibuat oleh pengguna.</p></article>
        <article class="field-card"><h2>Profil</h2><p>Perbarui nama dan informasi kontak akun.</p></article>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
