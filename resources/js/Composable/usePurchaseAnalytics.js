// resources/js/Composable/usePurchaseAnalytics.js

import { ref, computed, toRefs, isRef } from "vue";

/**
 * Custom hook untuk menganalisis dan menghitung selisih (discrepancy)
 * antara data Purchase Order (snapshot) dan data Penerimaan (current item data).
 *
 * @param {object} itemsRef - Ref/Reactive array dari PurchaseItem objects.
 * @returns {object} - Reactive metrics.
 */
export function usePurchaseAnalytics(itemsRef) {
    const itemsData = computed(() =>
        isRef(itemsRef) ? itemsRef.value : itemsRef
    );
    // Fungsi pembantu untuk memastikan nilai numerik yang aman (default 0 jika null/undefined)
    const safeNum = (value) => parseFloat(value) || 0;

    // Computed utama yang melakukan satu loop untuk menghitung semua metrik
    const analytics = computed(() => {
        const items = itemsData.value || [];

        let metrics = {
            kosong: 0,
            baru_pengganti: 0,
            total_macam_dipesan: 0,
            total_macam_diterima: 0,
            total_qty_dipesan: 0,
            total_qty_diterima: 0,
            total_rupiah_dipesan: 0,
            total_rupiah_diterima: 0,
            total_barang_sesuai: 0,
            macam_harga_naik: 0,
            macam_harga_turun: 0,
            produk_qty_lebih: 0, // Sum selisih
            produk_qty_kurang: 0, // Sum selisih
        };

        items.forEach((item) => {
            const snapshot = item.product_snapshot || {};

            // Data Ordered (PO Awal)
            const qtyOrdered = safeNum(snapshot.quantity);
            const priceOrdered = safeNum(snapshot.purchase_price);

            // Data Diterima (Final/Koreksi)
            const qtyReceived = safeNum(item.quantity);
            const priceReceived = safeNum(item.purchase_price);

            // --- A. PERHITUNGAN GLOBAL (Summing) ---
            metrics.total_qty_dipesan += qtyOrdered;
            metrics.total_qty_diterima += qtyReceived;
            metrics.total_rupiah_dipesan += qtyOrdered * priceOrdered;
            metrics.total_rupiah_diterima += qtyReceived * priceReceived;

            // --- B. ANALISIS STATUS PER BARIS ---

            // 1. Barang Baru / Substitusi
            if (qtyOrdered === 0 && qtyReceived > 0) {
                metrics.baru_pengganti += 1;
            }

            // 2. Total Macam Dipesan (Macam unik = baris item dengan qtyOrdered > 0)
            if (qtyOrdered > 0) {
                metrics.total_macam_dipesan += 1;

                // 3. Status Kosong (Dipesan > 0, Diterima 0)
                if (qtyReceived === 0) {
                    metrics.kosong += 1;
                }
            }

            // 4. Total Macam Diterima (Macam unik = baris item dengan qtyReceived > 0)
            if (qtyReceived > 0) {
                metrics.total_macam_diterima += 1;
            }

            // 5. Qty Lebih atau Kurang
            if (qtyReceived > qtyOrdered) {
                metrics.produk_qty_lebih += qtyReceived - qtyOrdered;
            } else if (qtyReceived < qtyOrdered) {
                metrics.produk_qty_kurang += qtyOrdered - qtyReceived;
            }

            // 6. Status Harga
            const isQtyPerfect = qtyReceived === qtyOrdered && qtyOrdered > 0;
            const isPricePerfect = priceReceived === priceOrdered;

            if (isQtyPerfect) {
                if (priceReceived > priceOrdered) {
                    metrics.macam_harga_naik += 1;
                } else if (priceReceived < priceOrdered) {
                    metrics.macam_harga_turun += 1;
                }
            }

            // 7. Total Barang Sesuai (Qty dan Harga sama-sama sempurna)
            if (isQtyPerfect && isPricePerfect) {
                metrics.total_barang_sesuai += 1;
            }
        });

        console.log(itemsData);
        // Catatan: Metrik "macam" di sini berarti "jumlah baris item unik" yang memenuhi kriteria.
        return metrics;
    });

    return {
        // [KRITIS]: Gunakan spread operator untuk mengembalikan semua properti secara reaktif
        ...toRefs(analytics.value),
        analytics, // Mengembalikan objek computed itu sendiri
    };
}
