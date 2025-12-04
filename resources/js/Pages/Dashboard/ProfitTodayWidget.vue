<script setup>
const props = defineProps({
    stats: Object, // { profit, revenue, transactions, profit_trend, daily_margin }
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
</script>

<template>
    <div
        class="relative flex flex-col justify-between h-full overflow-hidden text-white border shadow-lg bg-gradient-to-br from-emerald-600 to-teal-500 rounded-2xl group border-emerald-500/50"
    >
        <div
            class="absolute top-0 right-0 w-40 h-40 -mt-8 -mr-8 transition duration-700 bg-white rounded-full opacity-10 blur-2xl group-hover:scale-110"
        ></div>
        <div
            class="absolute bottom-0 left-0 w-32 h-32 -mb-8 -ml-8 rounded-full bg-emerald-400 opacity-20 blur-2xl"
        ></div>

        <div class="relative z-10 flex items-start justify-between p-6 pb-2">
            <div>
                <p
                    class="mb-1 text-xs font-medium tracking-widest uppercase text-emerald-100"
                >
                    Profit Bersih Hari Ini
                </p>
                <div class="flex items-center gap-2">
                    <h2 class="text-4xl font-black tracking-tight">
                        {{ formatRupiah(stats.profit) }}
                    </h2>
                </div>
            </div>

            <div
                class="p-2.5 bg-white/20 backdrop-blur-md rounded-xl shadow-inner border border-white/10"
            >
                <svg
                    class="w-6 h-6 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
            </div>
        </div>

        <div class="relative z-10 px-6 py-2">
            <div class="flex items-center gap-2 mb-4">
                <span
                    class="flex items-center gap-1 px-2 py-1 text-xs font-bold border rounded-lg shadow-sm backdrop-blur-sm border-white/10"
                    :class="
                        stats.profit_trend.direction === 'up'
                            ? 'bg-emerald-400/20 text-emerald-50'
                            : 'bg-red-500/20 text-red-50'
                    "
                >
                    <span v-if="stats.profit_trend.direction === 'up'">↗</span>
                    <span v-else>↘</span>
                    {{ Math.abs(stats.profit_trend.percent) }}%
                    <span class="font-normal opacity-80 text-[10px]"
                        >vs Kemarin</span
                    >
                </span>

                <span class="text-xs text-emerald-100/80">
                    {{ stats.profit_trend.direction === "up" ? "+" : "-" }}
                    {{ formatRupiah(Math.abs(stats.profit_trend.diff)) }}
                </span>
            </div>

            <div
                class="flex justify-between mb-1 text-xs font-medium text-emerald-100"
            >
                <span>Margin {{ stats.daily_margin }}%</span>
                <span>Omzet: {{ formatRupiah(stats.revenue) }}</span>
            </div>

            <div
                class="w-full h-2 mb-4 overflow-hidden rounded-full bg-black/20 backdrop-blur-sm"
            >
                <div
                    class="h-full bg-white shadow-[0_0_10px_rgba(255,255,255,0.5)] transition-all duration-1000"
                    :style="{ width: Math.min(100, stats.daily_margin) + '%' }"
                ></div>
            </div>
        </div>

        <div class="relative z-10 px-6 pt-2 pb-6 border-t border-white/10">
            <div
                class="flex items-center justify-between text-xs font-medium text-emerald-50"
            >
                <span class="flex items-center gap-1.5 opacity-80">
                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                        ></path>
                    </svg>
                    {{ stats.transactions }} Transaksi Berhasil
                </span>
                <span
                    class="bg-white/20 px-2 py-0.5 rounded text-[10px] tracking-wider uppercase font-bold"
                >
                    Live
                </span>
            </div>
        </div>
    </div>
</template>
