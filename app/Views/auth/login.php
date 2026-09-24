<?php $title = 'Login | Sportiva'; require __DIR__ . '/../layouts/header.php'; ?>
<main class="container">
    <section class="booking-layout">
        <div>
            <p class="eyebrow">AKSES AKUN</p>
            <h1>Login pengguna</h1>
            <p class="muted">Masuk untuk mengelola reservasi lapangan olahraga.</p>
        </div>
        <form class="booking-form" method="post" action="/login">
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <button type="submit">Masuk</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
