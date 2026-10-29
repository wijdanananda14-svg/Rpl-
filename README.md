# Sistem Reservasi Lapangan Olahraga

## Deskripsi

Sistem Reservasi Lapangan Olahraga adalah aplikasi berbasis web untuk membantu pengguna mencari lapangan olahraga, melihat jadwal yang tersedia, dan melakukan reservasi secara online. Jenis lapangan yang dapat dikelola meliputi lapangan futsal, badminton, dan basket.

## Tujuan

- Memudahkan pengguna memesan lapangan tanpa datang langsung ke lokasi.
- Menampilkan jadwal lapangan secara teratur dan mudah dipahami.
- Membantu pengelola mengatur data lapangan, jadwal, dan reservasi.
- Mengurangi risiko bentrok jadwal atau pencatatan reservasi yang keliru.

## Manfaat

- Pengguna dapat menghemat waktu dalam proses pemesanan.
- Pengguna dapat memilih lapangan dan waktu sesuai kebutuhan.
- Pengelola memiliki pencatatan reservasi yang lebih rapi.
- Proses bisnis reservasi menjadi lebih cepat dan transparan.
- Data reservasi dapat digunakan untuk membuat laporan.

## Pengguna Sistem

### 1. Pengguna atau Pelanggan

Pengguna dapat membuat akun, melihat lapangan, mengecek jadwal, melakukan reservasi, melakukan pembayaran simulasi, dan melihat riwayat pemesanan.

### 2. Admin atau Pengelola

Admin dapat mengelola data lapangan, mengatur jadwal, memeriksa reservasi, memperbarui status pembayaran, dan melihat laporan reservasi.

## Fitur Utama

- Registrasi dan login pengguna.
- Melihat daftar lapangan berdasarkan jenis olahraga.
- Melihat detail lapangan, harga, fasilitas, dan jadwal tersedia.
- Melakukan booking lapangan berdasarkan tanggal dan waktu.
- Pembayaran dalam bentuk simulasi.
- Melihat status dan riwayat pemesanan.
- Pembatalan reservasi sesuai aturan yang ditetapkan.
- Admin mengelola data lapangan dan jadwal.
- Admin mengelola status reservasi dan pembayaran.
- Admin melihat laporan data reservasi.

## Alur Sistem

### Alur Pengguna

1. Pengguna membuka sistem.
2. Pengguna melakukan registrasi atau login.
3. Pengguna memilih jenis dan lapangan olahraga.
4. Pengguna memilih tanggal serta jam yang tersedia.
5. Sistem memeriksa ketersediaan jadwal.
6. Pengguna mengisi data reservasi.
7. Sistem membuat pesanan dengan status menunggu pembayaran.
8. Pengguna melakukan pembayaran simulasi.
9. Sistem mengubah status menjadi terkonfirmasi.
10. Pengguna dapat melihat bukti dan riwayat reservasi.

### Alur Admin

1. Admin login ke sistem.
2. Admin menambahkan atau memperbarui data lapangan.
3. Admin mengatur jadwal dan harga lapangan.
4. Admin memeriksa reservasi yang masuk.
5. Admin memperbarui status pembayaran atau reservasi.
6. Admin melihat laporan reservasi.

## Database

Database menggunakan SQLite karena ringan, mudah dijalankan, dan sesuai untuk project perkuliahan. Tabel utama yang digunakan:

- `users`: menyimpan data akun pengguna dan admin.
- `fields`: menyimpan nama, jenis, harga, fasilitas, dan status lapangan.
- `schedules`: menyimpan tanggal, jam mulai, jam selesai, dan status jadwal.
- `bookings`: menyimpan data pemesanan pengguna terhadap jadwal lapangan.
- `payments`: menyimpan metode, nominal, dan status pembayaran.

Relasi utama: satu pengguna dapat memiliki banyak booking, satu lapangan memiliki banyak jadwal, satu jadwal dapat memiliki satu booking aktif, dan satu booking memiliki satu data pembayaran.

## Halaman Sistem

### Halaman Pengguna

- Halaman beranda.
- Halaman registrasi.
- Halaman login.
- Halaman daftar lapangan.
- Halaman detail lapangan dan jadwal.
- Halaman form reservasi.
- Halaman pembayaran simulasi.
- Halaman konfirmasi reservasi.
- Halaman riwayat pemesanan.
- Halaman profil pengguna.

### Halaman Admin

- Halaman login admin.
- Dashboard admin.
- Halaman pengelolaan lapangan.
- Halaman pengelolaan jadwal.
- Halaman pengelolaan reservasi.
- Halaman pengelolaan pembayaran.
- Halaman laporan.

## Teknologi dan Bahasa Pemrograman

Teknologi yang dipilih karena mudah dipelajari dan dijalankan:

- **PHP Native** untuk logika aplikasi server.
- **HTML dan CSS** untuk struktur serta tampilan halaman.
- **JavaScript** untuk interaksi sederhana pada halaman.
- **SQLite** untuk database.
- **Bootstrap** untuk membantu membuat tampilan responsif.
- **Visual Studio Code** sebagai editor kode.
- **XAMPP atau PHP Built-in Server** untuk menjalankan aplikasi.

## Metode Pengembangan

Metode yang digunakan adalah **Waterfall** dengan tahapan:

1. Analisis kebutuhan sistem.
2. Perancangan alur, database, dan antarmuka.
3. Implementasi program.
4. Pengujian setiap fitur.
5. Pemeliharaan dan perbaikan sistem.

Metode ini dipilih karena kebutuhan project sudah cukup jelas dan sesuai untuk project perkuliahan dengan tahapan pengerjaan yang terstruktur.

## Pengujian

Pengujian dilakukan menggunakan metode **Black Box Testing**, yaitu memeriksa keluaran sistem berdasarkan masukan tanpa melihat kode program secara langsung.

Skenario pengujian utama:

- Registrasi dengan data valid dan tidak valid.
- Login dengan akun yang benar dan salah.
- Menampilkan daftar lapangan.
- Menampilkan jadwal yang tersedia.
- Mencegah booking pada jadwal yang sudah dipesan.
- Membuat reservasi dengan data lengkap.
- Menyelesaikan pembayaran simulasi.
- Menampilkan riwayat reservasi.
- Membatalkan reservasi sesuai aturan.
- Admin menambah, mengubah, dan menghapus data lapangan.
- Admin mengatur jadwal dan status reservasi.

## Status Project

**Implementasi awal.**

Dokumen kebutuhan, alur sistem, rancangan database, daftar halaman, teknologi, metode pengembangan, dan rencana pengujian telah ditentukan. Implementasi awal sudah menyediakan daftar lapangan, form reservasi, pengecekan bentrok jadwal, penyimpanan SQLite, riwayat reservasi, dan login admin.

## Menjalankan dan Memverifikasi Aplikasi

### Persyaratan

- PHP CLI dengan ekstensi `sqlite3` dan `session`.
- Browser web.

### Menjalankan

Dari direktori utama proyek, jalankan:

```bash
php -S 127.0.0.1:8001 -t .
```

Buka `http://127.0.0.1:8001/` untuk halaman utama atau `http://127.0.0.1:8001/index.php?page=admin` untuk login admin. Hentikan server dengan `Ctrl+C`.

Saat aplikasi pertama kali dibuka, `index.php` menyiapkan tabel SQLite yang diperlukan dan membuat data awal lapangan serta akun admin jika belum tersedia. Database aplikasi tersimpan di `storage/reservasi.sqlite`.

### Struktur Komponen dan Aliran Data

- `index.php` adalah entry point yang saat ini menangani permintaan halaman utama dan admin, membaca form, memvalidasi booking, memeriksa bentrok jadwal, berinteraksi dengan SQLite, dan merender HTML.
- `storage/reservasi.sqlite` menyimpan data lapangan, akun admin, dan booking.
- `public/css/style.css` mengatur tampilan, sedangkan `public/js/app.js` menyediakan interaksi sisi browser.
- `app/Controllers`, `app/Models`, `app/Views`, `app/Config`, dan `routes/web.php` berisi scaffold MVC. Beberapa bagiannya belum menjadi jalur eksekusi halaman utama; misalnya `index.php` saat ini membuat koneksi SQLite secara langsung dan belum memakai `app/Config/Database.php`.
- Aliran booking: browser mengirim form ke `index.php` → server memeriksa kelengkapan data, format email/tanggal/waktu, dan ketersediaan jadwal → booking yang valid disimpan ke SQLite → daftar riwayat membaca data booking dan menampilkannya kembali sebagai HTML.
- Aliran admin: admin mengirim email dan password → server mencocokkan akun dan memverifikasi hash password di SQLite → sesi admin dibuat → dashboard menampilkan booking dan menyediakan pembaruan status.

### Hasil Pemeriksaan

Pemeriksaan lokal: server PHP berhasil dijalankan, halaman utama merespons HTTP 200 dan memuat UI “Main lebih terencana” serta “Lapangan tersedia”. Tes booking dan validasi dapat dijalankan dengan:

```bash
php tests/booking_validation.php
php tests/booking_workflow.php
php tests/admin_booking_status.php
git log -2 --oneline
```

### MVP Pertemuan 5: Booking Lapangan

- **Brief:** pelanggan dapat memesan slot lapangan yang aktif, tanpa jadwal lampau, input waktu tidak valid, atau booking yang bentrok.
- **Plan:** validasi input booking di controller; letakkan pemeriksaan lapangan aktif, bentrok, dan penyimpanan di model; uji kasus valid, invalid, overlap, slot berdampingan, dan pembatalan memakai SQLite in-memory agar tes tidak mengubah database aplikasi.
- **Acceptance criteria:** data booking valid tersimpan; tanggal lampau, tanggal kalender yang tidak ada, email/waktu tidak valid ditolak; lapangan nonaktif dan waktu tumpang tindih ditolak; booking yang dibatalkan tidak lagi mengunci slot.
- **Build:** `BookingController` memvalidasi data form, `Booking` memeriksa lapangan dan bentrok serta menyimpan booking, dan `index.php` menyajikan pesan hasilnya.
- **Verify:** `tests/booking_validation.php` menguji masukan valid/tidak valid dan `tests/booking_workflow.php` menguji penyimpanan SQLite, bentrok, lapangan nonaktif, slot berdampingan, dan pembatalan.
- **Demo:** buka `http://127.0.0.1:8001/`, pesan slot valid untuk melihat booking masuk ke riwayat, kemudian coba slot yang bentrok untuk melihat pesan penolakan. Gunakan tanggal mendatang dan data uji; booking demo disimpan di database lokal.

### MVP Pertemuan 5: Status Booking Admin

- **Brief:** admin yang sudah login dapat mengubah status reservasi melalui dashboard; status di luar pilihan atau ID yang tidak ditemukan tidak boleh mengubah data.
- **Plan:** gunakan daftar status terpusat di model untuk validasi dan pilihan form; wajibkan sesi admin sebelum menerima update; uji semua status, ID/status tidak valid, dan akses tanpa login.
- **Acceptance criteria:** tiga status yang didukung (`Menunggu pembayaran`, `Dikonfirmasi`, `Dibatalkan`) tersimpan; status tidak dikenal, ID tidak valid, dan booking yang tidak ada ditolak serta menampilkan pesan gagal; pengguna yang belum login tidak dapat menjalankan aksi update.
- **Build:** `Booking::updateStatus` memvalidasi status dan keberadaan booking sebelum menyimpan, sedangkan form dashboard menggunakan daftar status yang sama dan aksi terlindungi oleh sesi admin.
- **Verify:** `tests/admin_booking_status.php` memakai SQLite in-memory untuk mengecek tiap status, status tidak dikenal, ID invalid, dan booking tidak ditemukan. Periksa juga halaman admin melalui browser.
- **Demo:** buka `http://127.0.0.1:8001/index.php?page=admin`, login dengan akun demo di bagian Akses Admin, lalu ubah status booking dan tekan **Simpan**. Aksi tanpa sesi admin mengarahkan kembali ke login.

### Review untuk Presentasi

- **Yang dibantu AI Agent:** memisahkan validasi dan operasi booking ke controller/model, menambahkan tes acceptance SQLite, memakai satu daftar status yang sama untuk penyimpanan dan form admin, serta menjalankan tes dan smoke test HTTP.
- **Yang wajib direview mahasiswa:** periksa perubahan dengan `git show --stat` dan baca implementasi serta tes; jelaskan mengapa tanggal lampau, lapangan nonaktif, slot bentrok, dan status tak dikenal ditolak. Mahasiswa harus jujur menyebutkan perubahan lanjutan yang benar-benar ia lakukan sendiri—jangan mengklaim mengubah kode jika tidak melakukannya.
- **Bukti proses:** dua commit fitur terpisah dapat dilihat dengan `git log -2 --oneline`; ketiga perintah tes di atas harus lulus. Demo dijalankan langsung di browser; dokumentasi ini bukan rekaman video.

Draft scaffold atau diagram yang dibuat dengan bantuan AI tetap perlu dipahami dan diverifikasi oleh mahasiswa. Sebelum menjelaskan aplikasi, pahami tanggung jawab setiap komponen di atas serta data yang diterima, divalidasi, disimpan, dan ditampilkan kembali.

## Akses Admin

Buka halaman admin melalui `http://localhost:8001/index.php?page=admin`.

- Email: `admin@sportiva.test`
- Password: `admin123`

Akun admin dibuat otomatis saat aplikasi pertama kali dijalankan. Untuk penggunaan nyata, password bawaan harus diganti.

## P6: Penyempurnaan MVP Reservasi Lapangan

### MVP

MVP yang disempurnakan adalah reservasi lapangan olahraga: pelanggan membuat booking pada lapangan yang aktif dan admin mengelola statusnya.

### User Stories dan Acceptance Criteria

1. **Data pelanggan:** sebagai pelanggan, saya ingin mengirim nama dan email untuk reservasi. **AC:** nama kosong atau email salah ditolak; nama dan email yang valid diterima bersama data booking lainnya.
2. **Jadwal mendatang:** sebagai pelanggan, saya ingin memilih tanggal dan waktu bermain yang belum lewat. **AC:** tanggal kalender tidak valid, tanggal lampau, waktu dengan format salah, jam selesai yang tidak setelah jam mulai, dan waktu mulai hari ini yang sudah lewat/tepat saat ini ditolak.
3. **Lapangan aktif:** sebagai pelanggan, saya ingin memilih lapangan yang tersedia. **AC:** booking pada lapangan aktif tersimpan dengan status awal `Menunggu pembayaran`; ID lapangan yang tidak ada atau lapangan nonaktif ditolak tanpa menyimpan booking.
4. **Ketersediaan slot:** sebagai pelanggan, saya ingin sistem mencegah jadwal bentrok. **AC:** booking dengan waktu yang beririsan pada lapangan dan tanggal yang sama ditolak; slot berdampingan, tanggal/lapangan berbeda, serta slot yang booking sebelumnya dibatalkan tetap dapat dipesan.
5. **Status reservasi:** sebagai admin, saya ingin memperbarui status booking. **AC:** status `Menunggu pembayaran`, `Dikonfirmasi`, atau `Dibatalkan` disimpan; status di luar daftar dan ID booking yang tidak ada ditolak tanpa mengubah status tersimpan.

### Implementasi dan Verifikasi P6

Perubahan produksi P6 memperketat aturan waktu untuk booking hari ini dan menetapkan status awal secara eksplisit saat menyimpan booking. Tes P6 ada dalam lima file terpisah: `tests/p6_customer_details.php`, `tests/p6_schedule_rules.php`, `tests/p6_booking_creation.php`, `tests/p6_slot_availability.php`, dan `tests/p6_admin_status.php`.

Jalankan semua file tes PHP, termasuk tes sebelumnya, dari direktori proyek:

```bash
for test in tests/*.php; do
    printf '\n== %s ==\n' "$test"
    php "$test" || exit 1
done
```

Setiap file mencetak hasilnya dan keluar dengan kode bukan nol bila ada assertion gagal. Aplikasi dapat dijalankan menggunakan perintah PHP built-in server pada bagian sebelumnya; buka halaman utama dan admin untuk demo.
