# PRD: Inventra Vehicle Service Hub & Multi-Draft POS

## 1. Identitas Fitur
- **Nama Fitur:** Vehicle Service Hub & History (VSH)
- **Status:** Pengembangan Bertahap (Modular)
- **Tujuan:** Manajemen riwayat ganti oli berbasis kendaraan, mendukung loyalitas pelanggan, dan DSS stok.

## 2. Arsitektur Data (Database)

### A. Tabel: `vehicles`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `plate_number` | String (Unique) | Primary Identifier (Index). |
| `brand` | String | Merk (Honda, Yamaha, dll). |
| `model` | String | Tipe (Vario 150, Beat, dll). |
| `description` | Text (Nullable) | Ciri fisik (Warna, Modifikasi, dll). |
| `security_code` | String | Password (Default: Reverse dari plate_number). |
| `service_interval_km` | Integer | Default: 2000 (Bisa di-custom per motor). |
| `service_interval_days` | Integer | Default: 60 (Bisa di-custom per motor). |
| `engine_type` | Enum | `matic`, `manual`. |

### B. Tabel: `oil_service_logs`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `vehicle_id` | FK | Relasi ke `vehicles`. |
| `current_km` | Integer (Nullable) | Opsional (Jika spidometer rusak). |
| `engine_oil_id` | FK | Relasi ke `products`. |
| `gear_oil_id` | FK | Relasi ke `products` (Hanya untuk Matic). |
| `next_service_date` | Date | Kalkulasi: `current_date` + `service_interval_days`. |
| `next_service_km` | Integer (Nullable) | Kalkulasi: `current_km` + `service_interval_km`. |
| `notes` | String (Nullable) | Catatan tambahan. |

## 3. Alur Logika & Keamanan
- **Auto-Password:** Saat pendaftaran, `security_code` diisi otomatis dengan membalikkan string `plate_number` (Contoh: "B 1234 ABC" -> "CBA4321B").
- **Interval Fleksibel:** Perhitungan jatuh tempo servis tidak menggunakan angka global, melainkan mengambil variabel dari masing-masing row di tabel `vehicles`.
- **KM Opsional:** Jika `current_km` kosong, sistem hanya menggunakan `next_service_date` sebagai acuan reminder.

## 4. Spesifikasi Frontend (Vue 3 + Pinia)
- **Modular Composables:** Memecah `usePosRealtime.js` menjadi:
  1. `usePosState.js`: Mengelola array `carts[]` dan `activeCartIndex`.
  2. `usePosService.js`: Logika khusus pencarian kendaraan dan kalkulasi interval.
  3. `usePosDraft.js`: Sinkronisasi ke LocalStorage.
- **Multi-Draft POS:** User dapat menambah tab transaksi baru. Setiap tab menyimpan state-nya sendiri (Items, Vehicle Info, Mode).

## 5. Rencana Tahapan (Phasing)
1. **Tahap 1:** Migrasi Database & Model Laravel.
2. **Tahap 2:** Refactoring `usePosRealtime.js` menjadi modular (Clean Code).
3. **Tahap 3:** Implementasi Customer Hub (CRUD Kendaraan).
4. **Tahap 4:** Integrasi POS (Switch Mode & Multi-tab).