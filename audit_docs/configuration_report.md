# Laporan Konfigurasi - Pengujian Browser Lokal (Playwright)

## Ringkasan
Untuk mengatasi keterbatasan teknis pada *browser agent* internal (masalah variabel lingkungan), kami telah memigrasikan strategi pengujian UI ke solusi **Lokal** menggunakan **Playwright**. Ini memberikan Anda kontrol penuh atas lingkungan pengujian dan memungkinkan verifikasi visual langsung.

## Perubahan Konfigurasi

### 1. Instalasi Dependensi
Paket berikut telah ditambahkan ke `package.json`:
- `npm install -D @playwright/test`: Kerangka kerja pengujian.
- `npx playwright install chromium`: Browser biner untuk menjalankan tes.

### 2. Struktur Pengujian
File pengujian baru dibuat di:
> `tests/Browser/AuthTest.spec.js`

### 3. Logika Pengujian
Skrip tes (`AuthTest.spec.js`) telah dirancang khusus untuk menangani alur autentikasi Inventra, termasuk fitur keamanan **Captcha Matematika**.

**Alur Logika:**
1.  **Navigasi**: Membuka `http://127.0.0.1:8000/login`.
2.  **Identifikasi Elemen**: Mendeteksi dua angka Captcha dari elemen UI (kelas `.bg-lime-100.text-lime-700`).
3.  **Pemecahan Masalah**: Menjumlahkan kedua angka secara otomatis.
4.  **Input Data**:
    - Mengisi Email (`admin@dev.com`)
    - Mengisi Password (`password`)
    - Mengisi Jawaban Captcha
5.  **Submit**: Klik tombol "Masuk Sekarang".
6.  **Verifikasi**: Menunggu pengalihan ke `/dashboard` dan memastikan teks "Dashboard" muncul.

## Instruksi Penggunaan

Anda dapat menjalankan pengujian ini kapan saja menggunakan terminal (PowerShell/CMD):

**1. Jalankan dalam Mode Headless (Cepat, tanpa UI):**
```bash
npx playwright test tests/Browser/AuthTest.spec.js
```

**2. Jalankan dalam Mode Kepala (Visual, untuk Debugging):**
```bash
npx playwright test tests/Browser/AuthTest.spec.js --headed
```

**3. Melihat Laporan HTML:**
```bash
npx playwright show-report
```

## Status Saat Ini & Rekomendasi
- **Status**: Infrastruktur siap. Tes saat ini mungkin memerlukan penyesuaian selektor lebih lanjut tergantung pada perubahan UI di masa depan.
- **Debugging**: Jika tes gagal (timeout), periksa file screenshot (`debug-*.png`) yang dihasilkan otomatis di folder root proyek untuk melihat apa yang dilihat oleh bot saat kegagalan terjadi.
