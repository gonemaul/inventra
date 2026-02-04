# Rencana Implementasi - Setup Playwright Lokal

## Masalah
Alat *browser agent* internal mengalami kegagalan konfigurasi lingkungan (`$HOME` not set).

## Solusi
Melakukan konfigurasi dan instalasi **Playwright** langsung di dalam proyek `Inventra`. Ini memberikan kontrol penuh dan memungkinkan pengujian UI/UX yang dapat diulang oleh pengguna.

## Langkah-langkah
1.  **Instalasi Dependensi**:
    - `npm init playwright@latest` (atau install manual jika ingin kustom).
    - Kita akan gunakan `npm install -D @playwright/test` dan `npx playwright install chromium`.

2.  **Pembuatan Skrip Tes**:
    - Membuat folder `tests/Browser`.
    - Membuat file `tests/Browser/AuthTest.spec.js`.
    - Skenario: Buka Login -> Input Kredensial -> Submit -> Verifikasi URL Dashboard.

3.  **Eksekusi**:
    - Menjalankan `npx playwright test`.

4.  **Verifikasi**:
    - Melihat hasil tes di terminal.
