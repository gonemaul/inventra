# 🧠 BLUEPRINT DSS INVENTRA (Per Modul)

Visi: Sistem "Konsultan Bisnis" yang proaktif, bukan sekadar pencatat.

## 1. ⚙️ Core Engine (Backend)

-   **Tabel:** `smart_insights`
    -   Columns: `product_id`, `type` (restock/dead_stock/margin/trend), `severity` (critical/warning), `message`, `payload` (JSON bukti data), `is_read`.
-   **Scheduler:** Script background yang berjalan harian untuk mengisi tabel `smart_insights`.
-   **Logic:**
    -   _Smart Restock:_ Avg Daily Sales vs Current Stock.
    -   _Dead Stock:_ Last Transaction Date > 90 days.
    -   _Margin Alert:_ (Harga Jual - Harga Beli Baru) < Margin Min.
    -   _Trend Watcher:_ Sales Month Current vs Last Month > 30%.

## 2. 🏠 Modul Dashboard (Pusat Komando)

-   **Toko Health Score (0-100):** Gauge meter gabungan skor Stok + Cashflow + Profit.
-   **Action Cards:** List "To-Do" prioritas (misal: "3 Barang Kritis", "2 Hutang Jatuh Tempo").
-   **Cashflow Projection:** Alert teks prediksi saldo minggu depan.
-   **Top 5 Insights:** Cuplikan dari tabel `smart_insights` dengan urgensi 'critical'.

## 3. 📦 Modul Produk & Inventory

-   **List Produk:**
    -   Badge 🔴 (Stok Kritis).
    -   Badge 🔥 (Trending/Laris).
    -   Filter "Dead Stock" (Barang Mati).
-   **Detail Produk (Tab DSS):**
    -   **Smart Restock:** "Habis dalam X hari. Order Y pcs sekarang."
    -   **Dead Stock:** "Aset mandek Rp X. Saran: Diskon/Bundling."
    -   **Trend:** Grafik kenaikan penjualan.

## 4. 💰 Modul Keuangan

-   **Margin Guardian:** Notifikasi jika HPP naik tapi Harga Jual belum diupdate (Profit tergerus).
-   **Debt Prioritization:** Sorting hutang berdasarkan urgensi (Status Belum Lunas + Jatuh Tempo Terdekat).

## 5. 🚚 Modul Pembelian (Restock)

-   **Smart Order:** Fitur "Add from Recommendations" (Auto-fill keranjang belanja dari hasil analisa Smart Restock).
-   **Price Watch:** Alert jika harga beli dari supplier naik dibanding history sebelumnya.

## 6. 🤖 Notifikasi (Telegram)

-   **Morning Briefing:** Target & Tagihan hari ini.
-   **Realtime Alert:** Stok habis, Error sistem.
-   **Closing Report:** Rekap Omzet & Profit harian.
