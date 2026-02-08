<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/ReportPagination.vue";
import { useExport } from "@/Composable/useExport";

const props = defineProps({
    customers: Object,
});

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.customers.data.map((c, index) => ({
        Rank: (props.customers.current_page - 1) * props.customers.per_page + index + 1,
        Nama: c.customer?.name || 'Umum',
        Member: c.customer?.member_code || '-',
        Telp: c.customer?.phone || '-',
        'Jumlah Transaksi': c.visit_count,
        'Total Belanja': c.total_spent,
        'Terakhir Datang': c.last_seen
    }));
    exportToCsv('Laporan_Top_Customers', dataToExport);
};


const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "numeric",
              month: "short",
              year: "numeric",
          })
        : "-";

const getRankBadge = (index, page) => {
    // Rank logic: based on current page and index
    const rank = (props.customers.current_page - 1) * props.customers.per_page + index + 1;
    
    if (rank === 1) return { label: '🥇 #1', class: 'bg-yellow-100 text-yellow-800 border-yellow-300' };
    if (rank === 2) return { label: '🥈 #2', class: 'bg-gray-100 text-gray-800 border-gray-300' };
    if (rank === 3) return { label: '🥉 #3', class: 'bg-orange-100 text-orange-800 border-orange-300' };
    
    return { label: `#${rank}`, class: 'bg-white text-gray-500 border-gray-200' };
};
</script>

<template>
    <Head title="Pelanggan Setia" />

    <AuthenticatedLayout headerTitle="Top Customers">
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
            <!-- Intro Card -->
            <div class="p-6 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-lg relative overflow-hidden">
                <div class="relative z-10 max-w-2xl">
                    <h2 class="text-2xl font-bold mb-2">💎 Pelanggan Adalah Raja</h2>
                    <p class="text-blue-100 text-sm">
                        Berikut adalah daftar pelanggan dengan total belanja tertinggi. 
                        Gunakan data ini untuk memberikan reward atau promosi khusus bagi pelanggan setia Anda.
                    </p>
                </div>
                <div class="absolute right-0 bottom-0 opacity-10 p-4">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 w-20 text-center">Rank</th>
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4 text-center">Frekuensi Belanja</th>
                                <th class="px-6 py-4 text-right">Total Belanja</th>
                                <th class="px-6 py-4 text-center">Terakhir Datang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(item, index) in customers.data" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        class="px-3 py-1 text-xs font-black border rounded-full shadow-sm whitespace-nowrap"
                                        :class="getRankBadge(index).class"
                                    >
                                        {{ getRankBadge(index).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800 dark:text-white text-base">
                                        {{ item.customer?.name || 'Umum / Tanpa Nama' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Member: {{ item.customer?.member_code || '-' }} • 
                                        Telp: {{ item.customer?.phone || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-medium text-gray-600 dark:text-gray-300">
                                    {{ item.visit_count }}x
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="font-black text-emerald-600 text-base">
                                        {{ formatRupiah(item.total_spent) }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">Total Revenue</div>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500">
                                    {{ formatDate(item.last_seen) }}
                                </td>
                            </tr>
                             <tr v-if="customers.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    Belum ada data pelanggan yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                 <!-- Footer Pagination -->
                <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
