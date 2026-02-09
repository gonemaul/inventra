<script setup>
import { computed } from "vue";

const props = defineProps({
    summary: {
        type: Object,
        required: true,
        default: () => ({
            sales_today: 0,
            sales_week: 0,
            sales_month: 0,
            count_today: 0,
            period_today: '',
            period_week: '',
            period_month: '',
        }),
    },
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const stats = computed(() => [
    {
        label: "Penjualan Hari Ini",
        period: props.summary.period_today,
        value: formatRupiah(props.summary.sales_today),
        icon: "💰",
        colorClass: "bg-blue-500",
        bgClass: "bg-blue-50 border-blue-100 dark:bg-blue-900/20 dark:border-blue-800",
        textClass: "text-blue-600 dark:text-blue-400",
    },
    {
        label: "7 Hari Terakhir",
        period: props.summary.period_week,
        value: formatRupiah(props.summary.sales_week),
        icon: "📅",
        colorClass: "bg-purple-500",
        bgClass: "bg-purple-50 border-purple-100 dark:bg-purple-900/20 dark:border-purple-800",
        textClass: "text-purple-600 dark:text-purple-400",
    },
    {
        label: "30 Hari Terakhir",
        period: props.summary.period_month,
        value: formatRupiah(props.summary.sales_month),
        icon: "📈",
        colorClass: "bg-emerald-500",
        bgClass: "bg-emerald-50 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800",
        textClass: "text-emerald-600 dark:text-emerald-400",
    },
    {
        label: "Transaksi Hari Ini",
        period: props.summary.period_today,
        value: props.summary.count_today + " Nota",
        icon: "🧾",
        colorClass: "bg-orange-500",
        bgClass: "bg-orange-50 border-orange-100 dark:bg-orange-900/20 dark:border-orange-800",
        textClass: "text-orange-600 dark:text-orange-400",
    },
]);
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6">
        <div
            v-for="(stat, idx) in stats"
            :key="idx"
            class="relative p-4 lg:p-6 border rounded-2xl shadow-sm transition hover:shadow-md"
            :class="stat.bgClass"
        >
            <div class="flex items-center gap-3 lg:gap-4 mb-2">
                <div
                    class="w-8 h-8 lg:w-10 lg:h-10 rounded-full flex items-center justify-center text-white shadow-sm"
                    :class="stat.colorClass"
                >
                    <span class="text-sm lg:text-lg">{{ stat.icon }}</span>
                </div>
                <!-- Label for desktop -->
                <div class="hidden lg:block">
                     <h3 :class="stat.textClass"
                        class="text-sm font-bold uppercase tracking-wider"
                    >
                        {{ stat.label }}
                    </h3>
                    <p class="text-[12px] text-gray-500 font-mono">{{ stat.period }}</p>
                </div>
                <!-- Label for mobile (above value) -->
                <h3 :class="stat.textClass"
                    class="lg:hidden text-[10px] font-bold uppercase tracking-wider mb-0.5"
                >
                    {{ stat.label }} 
                </h3>
            </div>

            <div>
                <span class="text-[11px] lg:hidden font-mono normal-case text-gray-500">{{stat.period}}</span>
                <p
                    class="text-lg lg:text-2xl font-black truncate"
                    :class="stat.textClass"
                >
                    {{ stat.value }}
                </p>
            </div>
            
             <!-- Decorative Circle -->
            <div class="absolute -right-4 -bottom-4 w-16 h-16 rounded-full opacity-10" :class="stat.colorClass"></div>
        </div>
    </div>
</template>
