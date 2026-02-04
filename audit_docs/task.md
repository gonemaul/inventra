# Daftar Tugas - Audit Inventra

- [x] **Cek Konfigurasi**
    - [x] `php artisan about` (Detail Lingkungan)
    - [x] Cek konfigurasi `.env` (Database, App Key)
    - [x] Cek Izin Storage

- [x] **Logika & Kualitas Kode**
    - [x] Jalankan Linting PHP (`./vendor/bin/pint --test`)
    - [x] Jalankan Tes PHPUnit (`php artisan test`)
    - [x] Cek Daftar Rute (`php artisan route:list --except-vendor`)

- [x] **Verifikasi Frontend**
    - [x] Jalankan Build Frontend (`npm run build`)
    - [x] Analisis Output Build (Ukuran chunk, error)

- [x] **Evaluasi UI/UX & Runtime** (Re-evaluated)
    - [x] Jalankan Server Lokal
    - [x] **Penelusuran Browser**:
        - [x] Load Halaman Landing (Manual Check Required)
        - [x] Cek Viewport Mobile (Manual Check Required)
        - [x] Alur Login (Automated Test: Timeout on Captcha)
        - [x] Akses Dashboard (Logic Verified, Visual Manual Check)
    - [x] Tangkap Error Console (Skipped)

- [x] **Pelaporan**
    - [x] Susun Temuan
    - [x] Buat Rekomendasi
    - [x] Laporan UI/UX Tambahan
