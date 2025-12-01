📘 BLUEPRINT & ROADMAP: Sistem Manajemen Toko Cerdas (Inventra)
I. Visi & Tujuan Proyek
Misi: Mentransformasi toko oli/bengkel tradisional menjadi bisnis berbasis data. Fungsi Utama: Sistem tidak hanya mencatat administrasi, tetapi bertindak sebagai DSS (Decision Support System) yang memberikan rekomendasi cerdas untuk pembelian, penetapan harga, dan strategi penjualan.
Karakteristik Bisnis (Studi Kasus):
Model Toko: Campuran Fast Moving (Oli) & Slow Moving (Sparepart/Elektronik).
Fokus Utama: Oli (Butuh detail Merk, Tipe Mesin, Spesifikasi).
Pembelian: Hibrida (Online Marketplace & Offline Salesman).
Penjualan: Rekap Harian (Closingan), bukan kasir per transaksi.
Keuangan: Manajemen Hutang Ketat & HPP Asli (termasuk ongkir).

II. Arsitektur Modul & Alur Kerja (Workflow)

1. ⚙️ Pengaturan (Foundation)
   Tempat standarisasi data agar analisa akurat.
   Master Data Dasar: Kategori, Satuan, Ukuran, Supplier.
   Master Data Lanjutan (Upgrade): Merk (Brands) untuk analisa persaingan antar-brand.
2. 📦 Katalog Produk (Smart Catalog)
   Katalog digital yang detail dan kaya data.
   Struktur Data:
   Nama, Kode (SKU).
   Dimensi Baru: Kategori (mis: Oli), Merk (mis: Shell), Tipe (mis: Matic/Bebek).
   Otomatisasi: Stok & Harga Beli diperbarui otomatis oleh sistem.
   Analisa Mikro (Detail Produk): Grafik tren harga, status pergerakan (Fast/Slow), prediksi stok habis.
   Master Data Lanjutan (Upgrade):
   Merk (Brands): Untuk analisa persaingan antar-brand.
   Tipe Produk (Product Types): Sub-kategori dinamis yang bergantung pada Kategori (misal: Kategori Oli -> Tipe Matic).
3. 🚚 Pembelian (Smart Restock)
   Modul pengadaan barang dengan kecerdasan buatan sederhana.
   Fitur Cerdas (DSS):
   Rekomendasi Restock: Saat pilih Supplier A, sistem menyodorkan daftar barang Supplier A yang stoknya kritis.
   Rekomendasi HPP: Menghitung modal asli (Harga + Ongkir Online / Biaya Lain).
   Alur Kerja Ketat: Draft -> Dipesan -> Dikirim -> Diterima -> Validasi (Checking) -> Selesai. (Stok hanya bertambah setelah Validasi Fisik).
4. 🛒 Penjualan (Sales Recap)
   Input cepat untuk toko sibuk.
   Metode: Stock Opname Harian / Rekap Nota Manual.
   Output: Menghitung profit harian secara otomatis (Omzet - HPP saat itu).
   Data untuk Analisa: Menjadi sumber data utama untuk fitur Forecasting dan Tren Musiman.
5. 💰 Keuangan (Financial Health)
   Mengelola arus kas dan kewajiban.
   Fungsi: Pelacakan Hutang (Nota Supplier), Jadwal Jatuh Tempo, dan Pembayaran Cicilan.
   Analisa: Membedakan uang tunai vs uang mandek di stok mati.
6. 📊 Laporan & Strategi (Strategic Dashboard)
   Pandangan helikopter untuk Owner.
   Analisa Tren: "Oli Matic naik, Oli Diesel turun."
   Analisa Merk: "Merk X paling laku, Merk Y margin paling besar."
   Strategi: Rekomendasi diskon untuk Dead Stock dan Bundling produk.

III. Desain Database (Schema Final)
A. Master Data
categories, units, sizes, suppliers (Standar).
brands (Baru): id, name, logo.
product_types (Baru - Hirarki):
id, name, code.
category_id: Relasi ke categories (Parent). Ini memastikan Tipe "Matic" hanya muncul jika Kategori "Oli" dipilih.
B. Katalog Produk
products
Relasi: category_id, unit_id, size_id, supplier_id, brand_id.
product_type_id: Relasi ke product_types (menggantikan kolom string manual).
Atribut: code, name, image_path.
Klasifikasi: product_type (Matic, Bebek, Tubeless, dll).
Nilai: stock, min_stock, purchase_price (Harga Beli Terakhir), selling_price.
C. Modul Pembelian
purchases (Transaksi)
reference_no, transaction_date.
status (draft, ordered, shipped, received, checking, completed, cancelled).
Keuangan: shipping_cost, other_costs (Penting untuk HPP Online).
Analisa: received_at (Hitung Lead Time).
purchase_invoices (Nota & Hutang)
invoice_number, invoice_image, total_amount.
Status Bayar: payment_status (unpaid, partial, paid), amount_paid.
Tanggal: due_date (Jatuh tempo), paid_at.
purchase_items (Detail Barang)
product_snapshot (JSON), quantity, purchase_price.
Quality Control: rejected_quantity (Barang rusak).

status barang diterima:
Status Kuantitas (Inventaris)
Label Status
Warna UI
Definisi & Aturan Logika (Rule)
SESUAI
Hijau
Logika: Qty Diterima == Qty Dipesan

Barang datang dengan jumlah yang tepat sesuai pesanan.
BARANG BARU / SUBSTITUSI
Biru
Logika: Qty Dipesan == 0 DAN Qty Diterima > 0

Item ini tidak ada dalam PO awal, namun muncul di Nota Fisik (Barang tambahan atau pengganti barang kosong).
BARANG KOSONG
Merah Tua
Logika: Qty Dipesan > 0 DAN Qty Diterima == 0

Item dipesan, namun tidak dikirim sama sekali oleh supplier (Stok supplier habis).
QTY KURANG
Merah
Logika: Qty Diterima < Qty Dipesan DAN Qty Diterima > 0

Barang datang sebagian. Jumlah fisik lebih sedikit dari PO.
QTY LEBIH
Ungu
Logika: Qty Diterima > Qty Dipesan

Barang datang melebihi pesanan (Bonus atau kesalahan kirim supplier).

Status Harga (Keuangan)

Label Status
Warna UI
Definisi & Aturan Logika (Rule)
HARGA TETAP
Hijau
Logika: Harga Nota == Harga PO

Tidak ada perubahan harga modal.
HARGA NAIK
Kuning Tua
Logika: Harga Nota > Harga PO

Harga beli barang mengalami kenaikan dibandingkan saat pemesanan.
HARGA TURUN
Kuning Muda
Logika: Harga Nota < Harga PO

Harga beli barang lebih murah dibandingkan saat pemesanan.

Status Kombinasi (Comprehensive Status)
Sistem dapat menampilkan status gabungan jika terjadi ketidaksesuaian pada Kuantitas DAN Harga secara bersamaan.
Format Penulisan: [STATUS KUANTITAS] / [STATUS HARGA]
KURANG QTY / HARGA NAIK
Kondisi: Pesan 10 datang 5, dan harganya naik dari Rp10.000 jadi Rp11.000.
Tindakan: Admin harus memverifikasi apakah kekurangan ini disengaja karena kenaikan harga.
LEBIH QTY / HARGA TURUN
Kondisi: Pesan 10 datang 12, harga turun.
Tindakan: Kemungkinan bonus volume dari supplier.
BARANG BARU / HARGA NAIK
Kondisi: Barang pengganti yang harganya lebih mahal dari barang asli yang digantikan.
Status Administrasi (Linkage)

Label Status
Indikator
Definisi (Rule)
BELUM TERTAUT (Unlinked)
Checkbox Tersedia
Logika: purchase_invoice_id IS NULL

Item ini tercatat di PO tapi belum dicocokkan dengan Nota Fisik manapun. Perlu tindakan validasi.
TERTAUT (Linked)
Tampil di Tabel Edit
Logika: purchase_invoice_id IS NOT NULL

Item ini sudah divalidasi dan menjadi bagian dari kalkulasi total hutang pada nota tersebut.

D. Modul Penjualan
sales_recaps (Laporan Harian)
report_date, total_revenue, total_profit.
sale_recap_items (Detail)
quantity_sold, selling_price.
Profitability: capital_price (Modal saat dijual - dikunci untuk akurasi profit).

IV. Peta Jalan Pengerjaan (Development Roadmap)
Ini adalah panduan langkah demi langkah untuk membangun sistem ini dari posisinya saat ini.
✅ TAHAP 1: Pondasi & Master Data (SELESAI)
[x] Setup Laravel, Inertia, Vue.
[x] Authentication & Layout.
[x] Modul Settings (CRUD Kategori, Satuan, Ukuran, Supplier).
[x] Modul Produk Dasar (CRUD, Upload Gambar, Filter Pintar).
🚧 TAHAP 2: Upgrade Struktur Data (PRIORITAS SEKARANG)
Sebelum lanjut ke Purchase, kita harus siapkan wadah datanya.
Database Migration:
Buat tabel brands.
Buat tabel product_types (dengan foreign key category_id).
Update tabel products (tambah brand_id, ganti product_type string menjadi product_type_id).
Buat tabel-tabel purchases, purchase_invoices, purchase_items. 2. Update Modul Settings:
Tambah Tab Baru: "Merk" (CRUD Brand).
Tambah Tab Baru: "Tipe Produk" (CRUD Product Type). Fitur: Saat tambah tipe, User wajib pilih Kategori Induknya.
Update Modul Produk:
Update Form Create/Edit Produk untuk input Merk & Tipe.
Update Index/Filter Produk agar bisa filter by Merk.
Update Form Create/Edit Produk:
Tambahkan input Merk.
Implementasi Dependent Dropdown: Dropdown Tipe Produk hanya aktif dan terisi setelah User memilih Kategori.
🚀 TAHAP 3: Modul Pembelian (Inti Stok Masuk)
Membangun alur pengadaan barang yang cerdas.
Backend Service: Buat PurchaseService untuk handle logika transaksi DB dan validasi stok.
Halaman Create (Smart Form):
Implementasi usePurchaseCart.js (Engine Keranjang).
Implementasi UI Create.vue dengan Autocomplete & Staging Area.
Fitur Cerdas: Buat API & Modal Rekomendasi Restock (min_stock logic).
Halaman Index & Detail:
Tabel riwayat pembelian dengan status warna-warni.
Detail transaksi (View Only).
Fitur Checking (Validasi):
Halaman khusus untuk mencocokkan Item vs Nota Fisik yang di-upload.
📈 TAHAP 4: Modul Penjualan (Inti Stok Keluar)
Membangun sistem pencatat omzet.
Database: Migrasi tabel sales_recaps & sale_recap_items.
Halaman Rekap Harian: Form sederhana untuk input barang laku hari ini.
Logic HPP: Sistem otomatis mengambil purchase_price dari produk untuk menghitung profit harian.
💰 TAHAP 5: Keuangan & Dashboard (Analisa)
Menghidupkan "Otak" sistem.
Halaman Keuangan: List Nota Belum Lunas & Fitur Bayar Cicilan.
Dashboard Owner:
Widget "Barang Kritis".
Grafik "Tren Penjualan Oli vs Lainnya".
Info "Cashflow Hari Ini".
🧪 TAHAP 6: Testing & Deployment
Seeding Data Riil: Masukkan 50-100 data produk asli toko.
Simulasi: Jalankan alur beli -> jual selama 1 "bulan virtual" untuk cek akurasi stok dan profit.
Deploy: Pasang di server lokal toko atau cloud.

V. Catatan Teknis Tambahan
Framework: Laravel 11 + Vue 3 (Inertia.js).
Styling: Tailwind CSS.
Database: MySQL (Production), SQLite (Dev).
Pola Kode: Service Repository Pattern (Logic bisnis dipisah dari Controller).
Komponen UI: Reusable (DataTable, Modal, ActionLoader, PrimaryButton).
