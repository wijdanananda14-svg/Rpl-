# Menjalankan Sportiva MVP

## Persyaratan

- PHP 8.0+ dengan ekstensi `sqlite3` dan `session` (sudah diuji dengan PHP 8.5).
- Browser modern.

## Menjalankan aplikasi

Dari direktori repository:

```bash
php -S 127.0.0.1:8001 -t .
```

Buka:

- Aplikasi pelanggan: <http://127.0.0.1:8001/>
- Login admin: <http://127.0.0.1:8001/index.php?page=admin>

Hentikan server dengan `Ctrl+C`. Database SQLite dibuat/ditingkatkan otomatis di `storage/reservasi.sqlite`; jangan hapus file tersebut jika ingin mempertahankan data lokal.

## Akun demo admin

- Email: `admin@sportiva.test`
- Password: `admin123`

Akun admin dibuat otomatis jika database belum memiliki akun admin. Ganti kredensial bawaan sebelum aplikasi dipakai di lingkungan selain demo lokal.

## Demo alur pelanggan

1. Daftar melalui **Login / Daftar**. Password minimal 8 karakter.
2. Login, pilih lapangan aktif, tanggal mendatang, dan waktu mulai/selesai yang valid.
3. Buka **Dashboard saya** untuk melihat booking milik akun tersebut.
4. Pilih metode pembayaran simulasi lalu **Bayar simulasi**. Pembayaran menghitung harga lapangan per jam berdasarkan durasi dan mengonfirmasi booking; ini bukan pemrosesan uang sungguhan.
5. Untuk booking yang masih menunggu pembayaran, tombol **Batalkan** membatalkannya. Profil nama/email dapat diperbarui di dashboard.

## Demo alur admin

1. Login admin menggunakan akun demo di atas.
2. Tinjau ringkasan booking dan pembayaran.
3. Tambah/perbarui lapangan atau aktifkan/nonaktifkan lapangan.
4. Tambah jadwal tersedia. Jadwal yang sudah dikonfigurasi untuk tanggal dan lapangan tersebut membatasi booking pada waktu yang dibuka; jadwal yang ditutup menolak booking baru.
5. Perbarui status booking dari daftar status yang disediakan.

## Menjalankan seluruh unit test/regresi

Dari direktori repository:

```bash
for test in tests/*.php; do
    printf '\n== %s ==\n' "$test"
    php "$test" || exit 1
done
```

Setiap test memakai SQLite in-memory atau database sementara sehingga tidak mengubah database demo, kecuali alur HTTP manual yang kamu jalankan di browser sendiri.

## Database terpisah untuk demo/test manual

Untuk memakai database lain tanpa menyentuh data demo:

```bash
SPORTIVA_DB_PATH=/tmp/sportiva-demo.sqlite php -S 127.0.0.1:8002 -t .
```

Gunakan alamat <http://127.0.0.1:8002/>. Database terpisah dibuat otomatis.

## Merekam `demo.mp4`

Rekam layar secara lokal saat menjalankan langkah demo pelanggan dan admin di atas, lalu simpan hasil rekaman sebagai `demo.mp4` di direktori pengumpulan. Repository ini tidak menyertakan rekaman, karena perekaman harus dilakukan secara nyata oleh presenter dan tidak boleh diganti dengan video tiruan.
