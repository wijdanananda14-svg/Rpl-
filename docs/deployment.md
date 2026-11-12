# Deployment Sportiva ke VPS atau Single-board Computer

Panduan ini untuk deploy ke VPS Ubuntu/Debian atau Raspberry Pi/Orange Pi berbasis Debian/Armbian. Aplikasi menggunakan PHP dan SQLite, jadi tidak membutuhkan Node.js, database server terpisah, atau proses build frontend.

## Persyaratan server

- PHP 8.0 atau lebih baru.
- Apache 2.4 dengan PHP (mod_php atau PHP-FPM).
- Ekstensi PHP `sqlite3` dan `session`.
- Akses HTTPS untuk alamat publik. Untuk IP tanpa domain, siapkan sertifikat yang dipercaya reviewer atau gunakan domain.

## Siapkan Raspberry Pi atau Orange Pi

1. Siapkan perangkat dengan RAM minimal 1 GB, kartu microSD/eMMC yang sehat, catu daya, dan koneksi internet yang stabil. Untuk perangkat baru, gunakan Raspberry Pi OS Lite 64-bit atau image Debian/Armbian resmi yang sesuai dengan model Orange Pi.
2. Saat menulis image OS, aktifkan SSH dan atur user serta password; konfigurasi Wi-Fi juga dapat dimasukkan jika perangkat tidak memakai Ethernet. Jangan gunakan akun `root` untuk login SSH.
3. Nyalakan perangkat dan cari alamat IP lokalnya dari router atau layar perangkat. Dari komputer yang berada di jaringan yang sama, masuk dengan:

   ```bash
   ssh USER@IP-LOKAL-PI
   ```

4. Perbarui OS:

   ```bash
   sudo apt update
   sudo apt full-upgrade -y
   ```

5. Untuk mengurangi masalah IP lokal yang berubah, buat DHCP reservation untuk Pi pada router. Perangkat perlu tetap menyala dan tersambung ke internet agar aplikasi selalu tersedia.

Jika image Orange Pi tidak menyediakan `apt`/Debian, pilih image Debian atau Armbian resmi untuk model tersebut; jangan lanjutkan perintah instalasi di bawah pada OS yang tidak kompatibel.

## Demo gratis di Vercel

Vercel mendukung container PHP menggunakan FrankenPHP dan Dockerfile. Konfigurasi repository memakai SQLite dan sesi di `/tmp` agar demo dapat berjalan tanpa server/database tambahan. **Ini hanya untuk demo sementara:** filesystem container bersifat sementara dan beberapa instance tidak berbagi file, sehingga akun, sesi, booking, jadwal, dan pembayaran simulasi dapat hilang atau berbeda setelah instance diganti, diarsipkan, atau request diarahkan ke instance lain. Jangan gunakan data penting atau data pribadi.

1. Pastikan perubahan repository, termasuk `Dockerfile` dan `Caddyfile`, sudah ada pada branch GitHub yang akan di-deploy.
2. Masuk ke Vercel dan pilih **Add New → Project**, lalu import repository `wijdanananda14-svg/Rpl-`.
3. Biarkan framework menggunakan preset **Container** yang terdeteksi dari `Dockerfile`; root directory adalah direktori repository.
4. Pada **Project Settings → Environment Variables**, tambahkan untuk Production:
   - `SPORTIVA_ADMIN_EMAIL`: alamat email admin demo.
   - `SPORTIVA_ADMIN_PASSWORD`: password unik minimal 12 karakter.
5. Deploy. Ketika selesai, buka domain `*.vercel.app` yang diberikan; alamat uji admin adalah `/index.php?page=admin`.
6. Untuk pengujian dengan data bersih, lakukan redeploy/recreate deployment bila database sementara sudah berisi data. Jalankan juga seluruh tes PHP secara lokal sebelum mengirim tautan kepada reviewer.

Jangan masukkan kredensial admin ke source code atau commit Git. Pengaturan di atas hanya membuat admin pada database sementara ketika instance baru mulai. Login dan alur booking mungkin tidak konsisten jika request berpindah instance. Untuk penggunaan yang stabil, pindahkan database ke layanan PostgreSQL dan sesi ke Redis/shared store sesuai panduan resmi Vercel.

Container PHP di Vercel menggunakan fitur Container runtime. Batas penggunaan gratis, ketersediaan fitur, dan syarat akun mengikuti kebijakan Vercel yang berlaku; periksa dashboard sebelum deploy. Vercel deployment ini menghasilkan domain `*.vercel.app`, bukan URL `https://IP/nama_aplikasi`.

## Siapkan VPS

1. Buat akun di penyedia VPS pilihan, lalu buat server **Ubuntu 24.04 LTS**, 1 vCPU, RAM minimal 1 GB, dan disk minimal 10 GB untuk demo kelas. Pastikan server mendapat IPv4 publik.
2. Pilih autentikasi SSH key saat membuat server; jangan mengaktifkan login root dengan password. Catat alamat IP dan nama user SSH dari penyedia.
3. Jika penyedia menyediakan firewall jaringan, izinkan SSH dari IP Anda dan port TCP 80/443 untuk web.
4. Sambungkan dari komputer Anda menggunakan user/IP yang diberikan:

   ```bash
   ssh USER@IP-VPS
   ```

   Jangan mengirim private key atau password server ke chat.

## Instalasi Apache dan PHP

Jalankan pada Pi atau VPS berbasis Debian/Ubuntu:

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-sqlite3 git ufw
sudo a2enmod headers
sudo systemctl enable --now apache2
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable
```

Pastikan aturan `OpenSSH` sudah diizinkan sebelum mengaktifkan UFW agar sesi SSH tidak terputus. Jika UFW tidak terpasang atau tidak didukung image OS, atur firewall menggunakan mekanisme distro/perangkat tersebut.

Pastikan versi PHP memenuhi syarat dan ekstensi SQLite aktif:

```bash
php -v
php -m | grep -E 'sqlite3|session'
```

## Pasang aplikasi di subfolder URL

Salin atau clone repository sehingga `index.php` berada di `/var/www/html/sportiva/index.php`. Alamat aplikasi menjadi `https://IP-ATAU-DOMAIN/sportiva/`. Pastikan `.htaccess` ikut disalin.

Untuk clone dari GitHub:

```bash
sudo git clone https://github.com/wijdanananda14-svg/Rpl- /var/www/html/sportiva
```

Jika repository dibuat private, gunakan SSH deploy key yang dikelola di server; jangan menaruh token/password di URL Git.

Aktifkan pembacaan `.htaccess` pada konfigurasi Apache untuk `/var/www/html` dengan `AllowOverride All`, lalu reload Apache:

```bash
sudo systemctl reload apache2
```

Contoh blok konfigurasi Apache:

```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

`.htaccess` proyek menonaktifkan directory listing dan memblokir akses web ke direktori kode, test, database, serta file SQLite. Jangan menghapus proteksi tersebut. Jika memakai Nginx, pindahkan aturan blokir ini ke konfigurasi server Nginx; `.htaccess` hanya berlaku di Apache.

## Konfigurasi admin production

Pada konfigurasi lingkungan PHP/Apache (bukan file di repository), set:

```text
APP_ENV=production
SPORTIVA_ADMIN_EMAIL=alamat-admin-yang-anda-kendalikan
SPORTIVA_ADMIN_PASSWORD=password-unik-minimal-12-karakter
```

Jangan gunakan email/password demo dan jangan commit kredensial ke Git. Variabel admin hanya dipakai ketika database belum mempunyai admin. Atur variabel sebelum pertama kali membuka aplikasi; startup production akan berhenti dengan pesan error jika kredensial belum lengkap atau password kurang dari 12 karakter.

Untuk Apache `mod_php`, variabel dapat diatur di konfigurasi virtual host menggunakan `SetEnv`. Untuk PHP-FPM, atur variabel pada pool PHP-FPM dan pastikan pool tidak menghapusnya melalui `clear_env`. Lindungi file konfigurasi yang memuat password agar hanya root dan layanan yang diperlukan dapat membacanya.

## Izin SQLite

Aplikasi membuat `storage/reservasi.sqlite` otomatis saat pertama kali dijalankan. User proses web harus dapat menulis ke direktori `storage`; batasi aksesnya ke user layanan web:

```bash
sudo chown -R www-data:www-data /var/www/html/sportiva/storage
sudo chmod 750 /var/www/html/sportiva/storage
```

Jangan menyalin database lokal/demo ke server baru. Buat backup berkala dan simpan backup di luar document root.

## HTTPS dan firewall

Gunakan sertifikat TLS yang valid untuk domain/IP. Untuk domain, sertifikat Let's Encrypt dapat dipasang dengan Certbot. Buka hanya port yang diperlukan (umumnya 22 untuk SSH yang dibatasi, 80 untuk redirect/validasi sertifikat, dan 443 untuk HTTPS). Batasi SSH dan jangan membuka port SQLite/database.

Pada Pi di jaringan rumah/kampus, akses lokal seperti `http://IP-LOKAL-PI/sportiva/` hanya terlihat oleh perangkat di jaringan yang sama. Agar reviewer dari internet dapat mengakses, perlu salah satu dari:

- IP publik dan router yang mendukung port forwarding TCP 80/443 ke alamat lokal Pi; batasi akses SSH dan jangan mem-forward port SSH secara terbuka.
- Tunnel HTTPS yang berjalan terus-menerus di Pi. URL tunnel biasanya berupa domain penyedia tunnel, bukan `https://IP/sportiva/`.

Sebagian koneksi memakai CGNAT atau firewall kampus sehingga port forwarding tidak berfungsi. Dalam kondisi itu gunakan tunnel atau jaringan dengan IP publik. Untuk HTTPS tanpa peringatan browser, gunakan domain dan sertifikat TLS yang valid; HTTPS langsung ke alamat IP dapat membutuhkan sertifikat khusus dan tidak selalu didukung penyedia sertifikat.

## Verifikasi deploy

1. Buka `https://IP-ATAU-DOMAIN/sportiva/` dan pastikan halaman utama tampil.
2. Pastikan CSS dan JavaScript termuat, lalu daftar akun pelanggan uji dan lakukan booking.
3. Login admin dengan kredensial production yang dikonfigurasi, lalu cek dashboard.
4. Pastikan URL seperti `/sportiva/storage/reservasi.sqlite`, `/sportiva/tests/`, dan `/sportiva/app/` tidak dapat diakses (Apache seharusnya merespons 403).
5. Jalankan test regresi di server atau sebelum upload:

   ```bash
   for test in tests/*.php; do
       printf '\n== %s ==\n' "$test"
       php "$test" || exit 1
   done
   ```

Untuk uji lokal tanpa mengubah database demo, lihat [HowToRun.md](../HowToRun.md). Pembayaran dalam aplikasi hanya simulasi, bukan transaksi uang sungguhan.

Preview homepage lokal yang diuji pada URL subfolder: [screenshot Sportiva](./sportiva-homepage.png). Screenshot ini bukan tautan deployment publik.
