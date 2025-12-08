<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    filters: Object,
    data: Array,
    summary: Object,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    form.get(route("reports.gross-profit"), { preserveScroll: true });
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
    return "text-gray-600 bg-gray-100"; // Rugi/Nol
};

// Sorting Client Side
const sortKey = ref("profit");
const sortOrder = ref("desc");

const sortedData = computed(() => {
    return [...props.data].sort((a, b) => {
        let valA = a[sortKey.value];
        let valB = b[sortKey.value];
        return sortOrder.value === "asc" ? valA - valB : valB - valA;
    });
});

const toggleSort = (key) => {
    if (sortKey.value === key)
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    else {
        sortKey.value = key;
        sortOrder.value = "desc";
    }
};
</script>

<template>
    <Head title="Analisa Laba Kotor" />

    <AuthenticatedLayout headerTitle="Laba Kotor Per Produk">
        <div class="space-y-6">
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
                class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50"
                        >
                            <tr>
                                <th
                                    class="px-6 py-3 cursor-pointer hover:text-blue-600"
                                    @click="toggleSort('name')"
                                >
                                    Produk
                                </th>
                                <th
                                    class="px-6 py-3 text-center cursor-pointer hover:text-blue-600"
                                    @click="toggleSort('qty')"
                                >
                                    Terjual
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-blue-600"
                                    @click="toggleSort('revenue')"
                                >
                                    Omzet
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-blue-600"
                                    @click="toggleSort('cogs')"
                                >
                                    Modal (HPP)
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-blue-600 bg-emerald-50/50 dark:bg-emerald-900/10"
                                    @click="toggleSort('profit')"
                                >
                                    Profit (Rp)
                                    <span v-if="sortKey === 'profit'">{{
                                        sortOrder === "asc" ? "↑" : "↓"
                                    }}</span>
                                </th>
                                <th
                                    class="px-6 py-3 text-center cursor-pointer hover:text-blue-600"
                                    @click="toggleSort('margin')"
                                >
                                    Margin (%)
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="(item, idx) in sortedData"
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
                            <tr v-if="data.length === 0">
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
