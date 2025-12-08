<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    products: Array,
    summary: Object,
    categories: Array,
    filters: Object,
});

const form = useForm({
    category_id: props.filters.category_id || "",
});

const applyFilter = () => {
    form.get(route("reports.stock-value"), { preserveScroll: true });
};

// Formatter Rupiah
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Sorting (Client Side) - Biar user enak sort by Nilai Tertinggi
const sortKey = ref("asset_value");
const sortOrder = ref("desc");

const sortedProducts = computed(() => {
    return [...props.products]
        .map((p) => ({
            ...p,
            asset_value: p.stock * p.purchase_price,
            potential_sale: p.stock * p.selling_price,
        }))
        .sort((a, b) => {
            let valA = a[sortKey.value];
            let valB = b[sortKey.value];
            return sortOrder.value === "asc" ? valA - valB : valB - valA;
        });
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortOrder.value = "desc"; // Default desc untuk angka
    }
};
</script>

<template>
    <Head title="Laporan Nilai Aset" />

    <AuthenticatedLayout headerTitle="Valuasi Aset Gudang">
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    class="relative p-6 overflow-hidden text-white shadow-lg md:col-span-2 bg-gradient-to-br from-gray-800 to-gray-900 dark:from-lime-700 dark:to-lime-950 rounded-2xl"
                >
                    <div class="relative z-10">
                        <p
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Total Nilai Aset (HPP)
                        </p>
                        <h2 class="mt-2 text-3xl font-black">
                            {{ formatRupiah(summary.total_asset_value) }}
                        </h2>
                        <p class="mt-2 text-xs text-gray-400">
                            Uang yang mengendap dalam bentuk
                            {{ summary.total_items }} unit barang.
                        </p>
                    </div>
                    <div class="absolute bottom-0 right-0 p-4 opacity-10">
                        <svg
                            class="w-24 h-24"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Potensi Omzet
                    </p>
                    <h2 class="mt-1 text-2xl font-bold text-blue-600">
                        {{ formatRupiah(summary.potential_revenue) }}
                    </h2>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Jika semua terjual habis
                    </p>
                </div>

                <div
                    class="p-6 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Estimasi Profit
                    </p>
                    <h2 class="mt-1 text-2xl font-bold text-lime-600">
                        {{ formatRupiah(summary.potential_profit) }}
                    </h2>
                    <p class="text-[10px] text-gray-400 mt-1">Margin Kotor</p>
                </div>
            </div>

            <div
                class="bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="flex items-center justify-between gap-4 p-4 border-b border-gray-100 dark:border-gray-700"
                >
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                        Rincian Per Produk
                    </h3>

                    <div class="w-64">
                        <select
                            v-model="form.category_id"
                            @change="applyFilter"
                            class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 focus:ring-lime-500"
                        >
                            <option value="">Semua Kategori</option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50"
                        >
                            <tr>
                                <th
                                    class="px-6 py-3 cursor-pointer hover:text-lime-600"
                                    @click="toggleSort('name')"
                                >
                                    Produk
                                    <span v-if="sortKey === 'name'">{{
                                        sortOrder === "asc" ? "↑" : "↓"
                                    }}</span>
                                </th>
                                <th
                                    class="px-6 py-3 text-center cursor-pointer hover:text-lime-600"
                                    @click="toggleSort('stock')"
                                >
                                    Stok
                                    <span v-if="sortKey === 'stock'">{{
                                        sortOrder === "asc" ? "↑" : "↓"
                                    }}</span>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-lime-600"
                                    @click="toggleSort('purchase_price')"
                                >
                                    HPP Satuan
                                    <span v-if="sortKey === 'purchase_price'">{{
                                        sortOrder === "asc" ? "↑" : "↓"
                                    }}</span>
                                </th>
                                <th
                                    class="px-6 py-3 font-bold text-right bg-gray-100 cursor-pointer hover:text-lime-600 dark:bg-gray-900/50"
                                    @click="toggleSort('asset_value')"
                                >
                                    Total Aset (HPP)
                                    <span v-if="sortKey === 'asset_value'">{{
                                        sortOrder === "asc" ? "↑" : "↓"
                                    }}</span>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-lime-600"
                                    @click="toggleSort('potential_sale')"
                                >
                                    Potensi Jual
                                    <span v-if="sortKey === 'potential_sale'">{{
                                        sortOrder === "asc" ? "↑" : "↓"
                                    }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="item in sortedProducts"
                                :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td class="px-6 py-3">
                                    <div
                                        class="font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ item.code }} •
                                        {{ item.category?.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="px-2 py-1 font-mono font-bold text-gray-700 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ item.stock }} {{ item.unit?.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-gray-500">
                                    {{ formatRupiah(item.purchase_price) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-black text-right text-gray-800 bg-gray-50/50 dark:text-white dark:bg-gray-800/50"
                                >
                                    {{ formatRupiah(item.asset_value) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-medium text-right text-blue-600"
                                >
                                    {{ formatRupiah(item.potential_sale) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="p-4 text-xs text-center text-gray-400 border-t border-gray-100 dark:border-gray-700"
                >
                    * HPP dihitung berdasarkan harga beli terakhir/rata-rata
                    yang tersimpan di master produk.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
