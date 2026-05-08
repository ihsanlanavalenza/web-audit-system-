# GitHub Remote Deploy Guide

Panduan ini untuk deploy production dari GitHub ke shared hosting cPanel menggunakan GitHub Actions + SSH/SCP.

Target implementasi saat ini:

- cPanel account: `auditinm`
- app directory: `/home/auditinm/web-audit-system-/`
- Laravel public directory: `/home/auditinm/web-audit-system-/public`

## 1) Prasyarat

- Akun cPanel aktif.
- PHP di hosting minimal `8.2`.
- SSH akses untuk user cPanel aktif (port `22`).
- Repo source code sudah ada di GitHub.
- Domain production sudah aktif.
- File `.env` production hanya ada di server.
- Domain menunjuk ke folder web root (`public_html`) dan akan diisi otomatis dari folder `public` Laravel saat deploy.

## 2) One-Time Setup di Server

Di cPanel File Manager, siapkan folder aplikasi (contoh):

- `/home/auditinm/web-audit-system-/`
- upload code akan masuk ke folder ini lewat workflow.
- set document root domain ke `/home/auditinm/web-audit-system-/public`

Siapkan file `.env` production langsung di server (jangan di git).

Contoh `.env` minimum untuk koneksi DB production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=auditinm_webaudit
DB_USERNAME=auditinm_dbuser
DB_PASSWORD=isi-password-db-di-server

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="WebAudit"
```

Catatan keamanan:

- Jangan commit password DB ke repository.
- Karena password sempat dibagikan di chat, sebaiknya rotate password DB setelah setup final selesai.

## 3) Setup GitHub Actions Secrets (satu per satu)

Masuk ke GitHub repository:

- Settings
- Secrets and variables
- Actions
- New repository secret

Tambahkan ini satu per satu:

1. `CPANEL_FTP_SERVER`
  - isi host server cPanel (IP/hostname untuk SSH)
  - contoh benar: `67.220.67.17`
  - jangan isi dengan URL panel seperti `http://...:2083/`
2. `CPANEL_FTP_USERNAME`
  - isi username cPanel
3. `CPANEL_FTP_PASSWORD`
  - isi password cPanel
4. `CPANEL_SSH_PORT` (opsional)
  - default `22`
5. `CPANEL_APP_DIR` (opsional)
  - default otomatis: `/home/<username>/web-audit-system-/`
  - contoh: `/home/auditinm/web-audit-system-/`
6. `CPANEL_TARGET_DIR`
  - isi web root domain, untuk kasus ini: `/home/auditinm/public_html/`

Catatan:

- Workflow sudah otomatis menjalankan `npm run build`.
- Workflow deploy tidak mengirim folder `docs` dan file markdown (`*.md`).
- Workflow mengunggah source ke `CPANEL_APP_DIR`, lalu sinkronisasi `CPANEL_APP_DIR/public` ke `CPANEL_TARGET_DIR`.
- Nama secret tetap memakai prefix `CPANEL_FTP_*` untuk kompatibilitas workflow lama, tetapi sekarang dipakai untuk koneksi SSH/SCP.

## 4) First Deploy Verification di cPanel

Setelah workflow pertama sukses, verifikasi:

1. Di cPanel File Manager, cek file sudah terupload ke target folder.
2. Pastikan folder `build` ada di `public_html` dan berisi `manifest.json`.
3. Buka domain dan pastikan halaman login normal.
4. Cek log aplikasi di `${CPANEL_APP_DIR}/storage/logs/laravel.log` tidak ada fatal error baru.
5. Jalankan di cPanel Terminal (jika tersedia):

```bash
cd /home/auditinm/web-audit-system-
php artisan migrate --force
php artisan storage:link
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setelah update/deploy berikutnya, jalankan kembali `php artisan config:clear` lalu `php artisan config:cache`.

Opsional untuk validasi koneksi DB:

```bash
php artisan migrate:status
```

## 5) Queue Worker di Shared Hosting (tanpa Supervisor)

Karena cPanel shared hosting biasanya tidak menyediakan process manager permanen, gunakan Cron Job.

Di cPanel -> Cron Jobs, tambahkan:

1. Scheduler Laravel

```bash
* * * * * /usr/local/bin/php /home/auditinm/web-audit-system-/artisan schedule:run >> /dev/null 2>&1
```

2. Queue worker mode stop-when-empty

```bash
* * * * * /usr/local/bin/php /home/auditinm/web-audit-system-/artisan queue:work --queue=mail,default --tries=5 --backoff=60 --timeout=120 --stop-when-empty >> /dev/null 2>&1
```

Pastikan kedua cron aktif. Uji followup manual:

```bash
php artisan audit:send-followup
```

## 6) Uji Auto Deploy dari Push Pertama ke `main`

Langkah uji aman:

1. Buat perubahan kecil di file non-kritis (contoh 1 baris di README).
2. Commit dan push ke `main`.
3. Buka tab Actions di GitHub, pastikan workflow `Deploy Production (cPanel)` hijau.
4. Reload website production dan cek perubahan sudah masuk.

## 7) Rollback Cepat

Jika deploy gagal setelah upload:

1. Revert commit terakhir di GitHub.
2. Push revert ke `main`.
3. Workflow akan deploy ulang versi sebelum perubahan.

## 8) Checklist Akhir

Lihat checklist operasional di:

- `docs/PRODUCTION_CHECKLIST.md`

