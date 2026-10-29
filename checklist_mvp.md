# Checklist Penyelesaian MVP Sportiva

Keterangan: **PASSED** berarti unit test yang disebut sudah dijalankan dan lulus di lingkungan lokal pada PHP 8.5. Pemeriksaan HTTP adalah smoke test aplikasi nyata, bukan unit test.

| # | Fitur MVP | Acceptance criteria / bukti | Status |
|---|---|---|---|
| 1 | Registrasi akun | Nama/email valid dan password minimal 8 karakter diterima; input tidak valid dan email duplikat ditolak; password disimpan sebagai hash. `tests/p7_account_workflow.php` | **PASSED** |
| 2 | Login dan role | Kredensial benar dapat login; password salah ditolak; role user tidak dapat login sebagai admin. `tests/p7_account_workflow.php` | **PASSED** |
| 3 | Profil pelanggan | Nama/email dapat diubah; email yang digunakan akun lain ditolak. `tests/p7_account_workflow.php` | **PASSED** |
| 4 | Daftar lapangan | Lapangan aktif ditampilkan dengan harga dan fasilitas; tampilan menyediakan state kosong jika tidak ada lapangan aktif. Smoke test halaman utama HTTP 200 | **PASSED** |
| 5 | Validasi booking | Lapangan, nama/email, tanggal kalender, tanggal lampau, rentang waktu, dan jam hari ini tervalidasi. `tests/booking_validation.php`, `tests/p6_customer_details.php`, `tests/p6_schedule_rules.php` | **PASSED** |
| 6 | Membuat booking | Lapangan aktif menerima booking; lapangan tidak ada/nonaktif ditolak; status awal `Menunggu pembayaran`. `tests/booking_workflow.php`, `tests/p6_booking_creation.php` | **PASSED** |
| 7 | Ketersediaan jadwal | Bentrok ditolak; slot berdampingan dan tanggal/lapangan berbeda diterima; booking batal melepas slot; jadwal admin membatasi waktu booking. `tests/p6_slot_availability.php`, `tests/p7_field_schedule_management.php` | **PASSED** |
| 8 | Riwayat dan kepemilikan booking | Dashboard menampilkan booking akun saat ini; tindakan pembayaran/pembatalan dibatasi berdasarkan pemilik. `tests/p7_payment_cancellation.php`; alur HTTP terisolasi | **PASSED** |
| 9 | Pembayaran simulasi | Metode yang didukung membayar sekali, nominal mengikuti durasi, booking berubah menjadi `Dikonfirmasi`; metode salah/pemilik lain/double pay ditolak. `tests/p7_payment_cancellation.php` | **PASSED** |
| 10 | Pembatalan | Pemilik dapat membatalkan booking yang menunggu pembayaran; booking terkonfirmasi dan booking akun lain tidak dapat dibatalkan. `tests/p7_payment_cancellation.php` | **PASSED** |
| 11 | Pengelolaan lapangan admin | Tambah/perbarui data dan aktif/nonaktif lapangan; nama/harga invalid ditolak. `tests/p7_field_schedule_management.php` | **PASSED** |
| 12 | Pengelolaan jadwal admin | Jadwal valid dapat dibuat/ditutup; waktu overlap dan booking di luar/jadwal tertutup ditolak. `tests/p7_field_schedule_management.php` | **PASSED** |
| 13 | Status booking admin | Tiga status yang diizinkan disimpan; status/ID booking yang salah ditolak. `tests/admin_booking_status.php`, `tests/p6_admin_status.php` | **PASSED** |
| 14 | Laporan admin | Ringkasan booking menunggu, dikonfirmasi, serta pendapatan pembayaran tampil pada dashboard. Smoke test HTTP end-to-end pembayaran dan laporan | **PASSED** |
| 15 | Database lama | Skema lama ditambah tabel pembayaran/jadwal dan kolom owner booking tanpa menghapus tabel lama. `tests/p7_database_migration.php` | **PASSED** |
| 16 | Aplikasi runnable | PHP built-in server; homepage, login admin, dan dashboard merespons HTTP 200; alur login → booking → pembayaran → dashboard admin diuji dengan DB sementara | **PASSED** |
| 17 | Video demo | Rekaman layar nyata urutan pelanggan dan admin, disimpan sebagai `demo.mp4` | **BELUM TERSEDIA — rekam lokal sesuai [HowToRun.md](./HowToRun.md)** |

## Hasil run semua test

Perintah:

```bash
for test in tests/*.php; do printf '\n== %s ==\n' "$test"; php "$test" || exit 1; done
```

**PASSED: seluruh 12 file test PHP** (3 tes awal, 5 tes P6, 4 tes P7). Jalankan kembali perintah tersebut setelah ada perubahan.
