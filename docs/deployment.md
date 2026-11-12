# Deployment Sportiva

Aplikasi PHP + SQLite ini dapat di-deploy ke Render menggunakan Docker, atau dipasang di VPS/Raspberry Pi/Orange Pi berbasis Debian.

## Deploy ke Render dengan Docker

Repository menyediakan `Dockerfile` berbasis image resmi `php:8.2-apache` dan `.dockerignore`. Image mengaktifkan `mod_rewrite`, `pdo_sqlite`, `pdo_mysql`, dan `sqlite3` (aplikasi saat ini menggunakan ekstensi `sqlite3`). Apache mendengarkan pada port `$PORT` yang disediakan Render. Render membuat URL publik `*.onrender.com`.

1. Push branch `main` ke GitHub, lalu di Render pilih **New + → Web Service** dan hubungkan repository `wijdanananda14-svg/Rpl-`.
2. Pilih **Docker** sebagai runtime dan direktori root (`.`) sebagai Dockerfile path/root directory.
3. Pada pengaturan service, atur health check path ke `/` (jika opsi tersedia).
4. Tambahkan environment variables:
   - `SPORTIVA_ADMIN_EMAIL`: email admin valid.
   - `SPORTIVA_ADMIN_PASSWORD`: password unik minimal 12 karakter.
   - `APP_ENV` sudah diatur `production` oleh Docker image.
5. Untuk mempertahankan SQLite saat deploy/restart, tambahkan **Persistent Disk** dengan mount path `/var/data`. Database disimpan di `/var/data/sportiva.sqlite`. Tanpa persistent disk, data SQLite dapat hilang saat instance diganti atau di-redeploy; sesi login juga tidak bertahan setelah restart.
6. Deploy, buka URL `*.onrender.com`, lalu uji homepage, CSS, registrasi, booking, dan login admin.

Jangan masukkan kredensial ke repository. Render free web service tidak menyediakan persistent disk; SQLite pada filesystem ephemeral hanya cocok untuk demo tanpa data yang perlu dipertahankan. Persistent disk dan batas paket mengikuti kebijakan Render yang berlaku. Pembayaran aplikasi ini hanya simulasi.

## VPS atau Raspberry Pi / Orange Pi

Persyaratan:

- Ubuntu/Debian atau Raspberry Pi OS/Armbian yang kompatibel.
- PHP 8.0+ dengan ekstensi `sqlite3` dan `session`.
- Apache 2.4 dan HTTPS untuk akses publik.
- Pi minimal RAM 1 GB, catu daya stabil, penyimpanan microSD/eMMC yang sehat, dan internet yang selalu aktif.

### Persiapan Raspberry Pi / Orange Pi

1. Pasang Raspberry Pi OS Lite 64-bit atau image Debian/Armbian resmi yang sesuai dengan model perangkat. Aktifkan SSH dan atur user non-root.
2. Sambungkan perangkat ke internet, cari alamat IP lokalnya di router, lalu akses dari komputer di jaringan yang sama:

   ```bash
   ssh USER@IP-LOKAL-PI
   ```

3. Perbarui OS dan buat DHCP reservation di router agar alamat IP lokal Pi tidak berubah:

   ```bash
   sudo apt update
   sudo apt full-upgrade -y
   ```

### Instalasi Apache, PHP, dan firewall

Jalankan di server Debian/Ubuntu:

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-sqlite3 git ufw
sudo a2enmod headers
sudo systemctl enable --now apache2
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable
```

Pastikan `OpenSSH` diizinkan sebelum mengaktifkan UFW agar koneksi SSH tidak terputus. Jika UFW tidak didukung image OS, gunakan mekanisme firewall distro tersebut. Pastikan ekstensi PHP tersedia:

```bash
php -v
php -m | grep -E 'sqlite3|session'
```

### Pasang aplikasi di subfolder

Clone repository sehingga entry point berada di `/var/www/html/sportiva/index.php`:

```bash
sudo git clone https://github.com/wijdanananda14-svg/Rpl- /var/www/html/sportiva
```

Pastikan `.htaccess` ikut tersalin dan Apache mengizinkan `AllowOverride All` untuk `/var/www/html`, lalu reload:

```bash
sudo systemctl reload apache2
```

Contoh konfigurasi direktori Apache:

```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

`.htaccess` menonaktifkan directory listing dan memblokir akses web ke kode serta database. Jika memakai Nginx, pindahkan aturan proteksi tersebut ke konfigurasi Nginx.

Aplikasi membuat SQLite di `storage/reservasi.sqlite`; beri akses tulis terbatas kepada user web:

```bash
sudo chown -R www-data:www-data /var/www/html/sportiva/storage
sudo chmod 750 /var/www/html/sportiva/storage
```

Sebelum pertama kali membuka aplikasi, set `APP_ENV=production`, `SPORTIVA_ADMIN_EMAIL`, dan `SPORTIVA_ADMIN_PASSWORD` pada konfigurasi lingkungan PHP/Apache. Password admin minimal 12 karakter. Jangan simpan kredensial di repository; untuk `mod_php` gunakan `SetEnv` di konfigurasi virtual host, dan lindungi file konfigurasi yang memuat password.

### Akses reviewer dari internet

URL lokal seperti `http://IP-LOKAL-PI/sportiva/` hanya terlihat di jaringan yang sama. Agar reviewer dari internet dapat mengakses Pi, diperlukan IP publik dan router dengan port forwarding TCP 80/443 ke Pi, atau tunnel HTTPS yang berjalan terus-menerus. Tunnel biasanya memberikan domain penyedia, bukan URL `https://IP/nama_aplikasi`. Port forwarding tidak berfungsi pada jaringan CGNAT atau sebagian jaringan kampus. Untuk HTTPS tepercaya, gunakan domain dan sertifikat TLS yang valid.

VPS umumnya sudah memiliki IP publik; arahkan domain/IP ke server, buka hanya port SSH yang dibatasi serta 80/443, dan pasang TLS. Jangan membuka port SQLite/database ke internet.

### Verifikasi VPS/Pi

1. Buka `https://IP-ATAU-DOMAIN/sportiva/`, lalu pastikan homepage, CSS, dan JavaScript termuat.
2. Daftar akun uji, buat booking, lalu cek dashboard admin dengan kredensial production.
3. Pastikan `/sportiva/storage/reservasi.sqlite`, `/sportiva/tests/`, dan `/sportiva/app/` tidak dapat diakses.
4. Jalankan test regresi:

   ```bash
   for test in tests/*.php; do
       printf '\n== %s ==\n' "$test"
       php "$test" || exit 1
   done
   ```

Untuk cara menjalankan lokal, lihat [HowToRun.md](../HowToRun.md). Pembayaran dalam aplikasi hanya simulasi. Preview homepage: [screenshot Sportiva](./sportiva-homepage.png).
