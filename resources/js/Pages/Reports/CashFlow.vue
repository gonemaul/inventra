<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    filters: Object,
    data: Object,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});
const applyFilter = () =>
    form.get(route("reports.cash-flow"), { preserveScroll: true });
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const chartOptions = {
    chart: { type: "bar", height: 350, toolbar: { show: false } },
    colors: ["#10b981", "#ef4444"],
    plotOptions: { bar: { borderRadius: 8, distributed: true } },
    xaxis: { categories: ["Uang Masuk", "Uang Keluar"] },
    dataLabels: { formatter: (val) => formatRupiah(val) },
    legend: { show: false },
};

const chartSeries = computed(() => [
    {
        name: "Total",
        data: [props.data.cash_in, props.data.cash_out],
    },
]);
</script>

<template>
    <Head title="Laporan Arus Kas" />
    <AuthenticatedLayout headerTitle="Arus Kas (Cashflow)">
        <div class="space-y-6">
            <div
                class="flex justify-end gap-2 p-3 bg-white shadow-sm dark:bg-gray-800 rounded-xl"
            >
                <input
                    type="date"
                    v-model="form.start_date"
                    class="text-sm border-gray-300 rounded-lg dark:bg-gray-700"
                />
                <input
                    type="date"
                    v-model="form.end_date"
                    class="text-sm border-gray-300 rounded-lg dark:bg-gray-700"
                />
                <button
                    @click="applyFilter"
                    class="px-4 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700"
                >
                    Cek
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div
                    class="p-6 bg-white border-l-4 border-green-500 shadow-sm dark:bg-gray-800 rounded-2xl"
                >
                    <p class="text-xs font-bold text-gray-400 uppercase">
                        Total Uang Masuk
                    </p>
                    <h3 class="text-2xl font-black text-green-600">
                        {{ formatRupiah(data.cash_in) }}
                    </h3>
                </div>
                <div
                    class="p-6 bg-white border-l-4 border-red-500 shadow-sm dark:bg-gray-800 rounded-2xl"
                >
                    <p class="text-xs font-bold text-gray-400 uppercase">
                        Total Uang Keluar
                    </p>
                    <h3 class="text-2xl font-black text-red-600">
                        {{ formatRupiah(data.cash_out) }}
                    </h3>
                </div>
                <div
                    class="p-6 text-white shadow-lg rounded-2xl"
                    :class="
                        data.net_flow >= 0 ? 'bg-blue-600' : 'bg-orange-500'
                    "
                >
                    <p class="text-xs font-bold uppercase opacity-80">
                        Net Cashflow
                    </p>
                    <h3 class="text-3xl font-black">
                        {{ formatRupiah(data.net_flow) }}
                    </h3>
                    <p class="mt-1 text-xs">
                        {{
                            data.net_flow >= 0
                                ? "Surplus (Positif)"
                                : "Defisit (Negatif)"
                        }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <h3 class="mb-4 font-bold dark:text-white">
                        Visualisasi Arus Kas
                    </h3>
                    <VueApexCharts
                        type="bar"
                        height="300"
                        :options="chartOptions"
                        :series="chartSeries"
                    ></VueApexCharts>
                </div>
                <div
                    class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <h3 class="mb-4 font-bold dark:text-white">Rincian</h3>
                    <ul class="space-y-4 text-sm">
                        <li
                            class="flex justify-between pb-2 border-b border-gray-100 dark:border-gray-700"
                        >
                            <span class="text-gray-600 dark:text-gray-400"
                                >Penjualan Tunai/DP</span
                            >
                            <span class="font-bold text-green-600"
                                >+
                                {{ formatRupiah(data.breakdown.sales) }}</span
                            >
                        </li>
                        <li
                            class="flex justify-between pb-2 border-b border-gray-100 dark:border-gray-700"
                        >
                            <span class="text-gray-600 dark:text-gray-400"
                                >Bayar Supplier</span
                            >
                            <span class="font-bold text-red-500"
                                >-
                                {{
                                    formatRupiah(
                                        data.breakdown.supplier_payment
                                    )
                                }}</span
                            >
                        </li>
                        <li
                            class="flex justify-between pb-2 border-b border-gray-100 dark:border-gray-700"
                        >
                            <span class="text-gray-600 dark:text-gray-400"
                                >Biaya Operasional</span
                            >
                            <span class="font-bold text-red-500"
                                >-
                                {{
                                    formatRupiah(data.breakdown.expenses)
                                }}</span
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
