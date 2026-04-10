# GitHub Remote Deploy Guide

Panduan ini untuk deploy production dari GitHub ke shared hosting cPanel menggunakan GitHub Actions + FTP.

Target implementasi saat ini:

- cPanel account: `auditinm`
- app directory: `/home/auditinm/web-audit-system-/`
- Laravel public directory: `/home/auditinm/web-audit-system-/public`

## 1) Prasyarat

- Akun cPanel aktif.
- PHP di hosting minimal `8.4` (wajib, mengikuti `composer.lock` saat ini).
- FTP/SFTP credentials dari cPanel.
- Repo source code sudah ada di GitHub.
- Domain production sudah aktif.
- File `.env` production hanya ada di server.
- Di cPanel, document root domain diarahkan ke folder `public` milik aplikasi Laravel.

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
  - isi host FTP dari cPanel (contoh `ftp.domainanda.com`)
2. `CPANEL_FTP_USERNAME`
  - isi username FTP
3. `CPANEL_FTP_PASSWORD`
  - isi password FTP
4. `CPANEL_FTP_PORT`
  - biasanya `21`
5. `CPANEL_FTP_PROTOCOL` (opsional)
  - isi `ftp` (default) atau `ftps` sesuai setting hosting
  - jika hosting mewajibkan FTPS, gunakan `ftps`
6. `CPANEL_TARGET_DIR`
  - gunakan `/home/auditinm/web-audit-system-/`
  - pastikan target direktori folder (akhiri dengan `/`)

Catatan:

- Workflow sudah otomatis menjalankan `npm run build`.
- Workflow deploy tidak mengirim folder `docs` dan file markdown (`*.md`).
- Jika error `ECONNREFUSED` pada step FTP, cek ulang kombinasi `CPANEL_FTP_SERVER`, `CPANEL_FTP_PORT`, dan `CPANEL_FTP_PROTOCOL` dari cPanel FTP Accounts.

## 4) First Deploy Verification di cPanel

Setelah workflow pertama sukses, verifikasi:

1. Di cPanel File Manager, cek file sudah terupload ke target folder.
2. Pastikan folder `public/build` ada dan berisi `manifest.json`.
3. Buka domain dan pastikan halaman login normal.
4. Cek `storage/logs/laravel.log` tidak ada fatal error baru.
5. Jalankan di cPanel Terminal (jika tersedia):

```bash
cd /home/auditinm/web-audit-system-
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

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

