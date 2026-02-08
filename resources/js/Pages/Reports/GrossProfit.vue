<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import Pagination from "@/Components/ReportPagination.vue";

import { useExport } from "@/Composable/useExport";

const props = defineProps({
    filters: Object,
    data: Object, // Changed to Object (Paginated)
    summary: Object,
});

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.data.data.map(p => ({
        Nama: p.name,
        Kategori: p.category,
        'Total Terjual': p.total_sold,
        'Omzet': p.revenue,
        'HPP Total': p.cogs,
        'Laba Kotor': p.profit,
        'Margin (%)': p.margin
    }));
    exportToCsv('Laporan_Laba_Kotor', dataToExport);
};


const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    sort: props.filters.sort || "profit",
    direction: props.filters.direction || "desc",
});

const applyFilter = () => {
    // Reset to page 1 handling handled by router get usually if page param omitted, 
    // but here we just pass filters. Pagination component handles page param.
    // Ensure sort params are included
    router.get(route("reports.gross-profit"), {
        start_date: form.start_date,
        end_date: form.end_date,
        sort: form.sort,
        direction: form.direction,
    }, { preserveScroll: true });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Helper Warna Margin
const getMarginColor = (margin) => {
    if (margin >= 25)
        return "text-emerald-600 bg-emerald-50 border-emerald-200"; // Bagus
    if (margin >= 10) return "text-yellow-600 bg-yellow-50 border-yellow-200"; // Standar
    if (margin > 0) return "text-red-600 bg-red-50 border-red-200"; // Tipis
    // Margin negatif or 0
    return "text-gray-600 bg-gray-100"; // Rugi/Nol
};

// Server Side Sorting
const sortKey = ref(props.filters.sort || "profit");
const sortOrder = ref(props.filters.direction || "desc");

const toggleSort = (key) => {
    if (sortKey.value === key)
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    else {
        sortKey.value = key;
        sortOrder.value = "desc";
    }
    
    form.sort = sortKey.value;
    form.direction = sortOrder.value;
    applyFilter();
};
</script>

<template>
    <Head title="Analisa Laba Kotor" />

    <AuthenticatedLayout headerTitle="Laba Kotor Per Produk">
        <div class="space-y-6 pb-20">
            <!-- Toolbar -->
            <div class="flex flex-col gap-4 mb-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('reports.index')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 transition bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-blue-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:text-white"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Kembali
                    </Link>
                </div>
                <div class="flex gap-2">
                    <button @click="doExport" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-green-600 rounded-lg shadow hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Export CSV
                    </button>
                    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="flex items-center gap-4 p-4 bg-white border border-gray-200 shadow-sm md:col-span-2 dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <span class="text-sm font-bold text-gray-500"
                        >Periode:</span
                    >
                    <input
                        type="date"
                        v-model="form.start_date"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                    <span class="text-gray-400">-</span>
                    <input
                        type="date"
                        v-model="form.end_date"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                    <button
                        @click="applyFilter"
                        class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700"
                    >
                        Analisa
                    </button>
                </div>

                <div
                    class="flex items-center justify-between p-4 text-white shadow-lg bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl"
                >
                    <div>
                        <p
                            class="text-xs font-bold tracking-widest uppercase text-emerald-100"
                        >
                            Total Laba Kotor
                        </p>
                        <h3 class="text-2xl font-black">
                            {{ formatRupiah(summary.total_profit) }}
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-emerald-100">Rata-rata Margin</p>
                        <p class="text-xl font-bold">
                            {{ summary.avg_margin }}%
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 relative"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50"
                        >
                            <tr>
                                <th
                                    class="px-6 py-3 cursor-pointer hover:text-blue-600 group"
                                    @click="toggleSort('name')"
                                >
                                    <div class="flex items-center gap-1">
                                        Produk
                                         <span class="text-gray-300 group-hover:text-blue-500" :class="{'text-blue-600': sortKey === 'name'}">
                                            <span v-if="sortKey === 'name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-center cursor-pointer hover:text-blue-600 group"
                                    @click="toggleSort('total_qty')"
                                >
                                     <div class="flex items-center justify-center gap-1">
                                        Terjual
                                        <span class="text-gray-300 group-hover:text-blue-500" :class="{'text-blue-600': sortKey === 'total_qty'}">
                                            <span v-if="sortKey === 'total_qty'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-blue-600 group"
                                    @click="toggleSort('total_revenue')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Omzet
                                        <span class="text-gray-300 group-hover:text-blue-500" :class="{'text-blue-600': sortKey === 'total_revenue'}">
                                            <span v-if="sortKey === 'total_revenue'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-blue-600 group"
                                    @click="toggleSort('total_cogs')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Modal (HPP)
                                        <span class="text-gray-300 group-hover:text-blue-500" :class="{'text-blue-600': sortKey === 'total_cogs'}">
                                            <span v-if="sortKey === 'total_cogs'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-blue-600 bg-emerald-50/50 dark:bg-emerald-900/10 group"
                                    @click="toggleSort('profit')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Profit (Rp)
                                        <span class="text-gray-300 group-hover:text-blue-500" :class="{'text-blue-600': sortKey === 'profit'}">
                                            <span v-if="sortKey === 'profit'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-center cursor-pointer hover:text-blue-600 group"
                                    @click="toggleSort('margin')"
                                >
                                     <div class="flex items-center justify-center gap-1">
                                        Margin (%)
                                        <span class="text-gray-300 group-hover:text-blue-500" :class="{'text-blue-600': sortKey === 'margin'}">
                                            <span v-if="sortKey === 'margin'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="(item, idx) in data.data"
                                :key="idx"
                                class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td class="px-6 py-3">
                                    <div
                                        class="font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ item.code }} • {{ item.category }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-3 font-bold text-center text-gray-600"
                                >
                                    {{ item.qty }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-500">
                                    {{ formatRupiah(item.revenue) }}
                                </td>
                                <td
                                    class="px-6 py-3 text-xs text-right text-red-400"
                                >
                                    -{{ formatRupiah(item.cogs) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-black text-right text-emerald-600 bg-emerald-50/30 dark:text-emerald-400 dark:bg-emerald-900/10"
                                >
                                    {{ formatRupiah(item.profit) }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="px-2 py-1 text-xs font-bold border rounded"
                                        :class="getMarginColor(item.margin)"
                                    >
                                        {{ item.margin }}%
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="data.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-8 text-center text-gray-400"
                                >
                                    Tidak ada data penjualan pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                 <!-- Footer Pagination -->
                <div
                    class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-center md:justify-end"
                >
                     <Pagination :links="data.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
