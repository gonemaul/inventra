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
        class="relative flex flex-col justify-between h-full overflow-hidden text-white border shadow-lg bg-gradient-to-br from-lime-500 to-green-600 rounded-2xl group border-lime-400/30"
    >
        <div
            class="absolute top-0 right-0 w-48 h-48 -mt-8 -mr-8 transition duration-1000 bg-white rounded-full opacity-20 blur-3xl group-hover:scale-110"
        ></div>
        <div
            class="absolute bottom-0 left-0 w-40 h-40 -mb-10 -ml-10 bg-yellow-300 rounded-full opacity-20 blur-3xl mix-blend-overlay"
        ></div>

        <div class="relative z-10 p-6 pb-2">
            <div class="flex items-start justify-between">
                <div>
                    <p
                        class="mb-1 text-xs font-bold tracking-widest uppercase text-lime-100 opacity-90"
                    >
                        Profit Bersih Hari Ini
                    </p>
                    <div class="flex items-center gap-2">
                        <h2
                            class="text-4xl font-black tracking-tight drop-shadow-sm"
                        >
                            {{ formatRupiah(stats.profit) }}
                        </h2>
                    </div>
                </div>

                <div
                    class="p-2.5 bg-white/20 backdrop-blur-md rounded-xl shadow-inner border border-white/20"
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
        </div>

        <div class="relative z-10 px-6 py-2">
            <div class="flex items-center gap-3 mb-5">
                <span
                    class="px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1 backdrop-blur-md shadow-sm border border-white/20"
                    :class="
                        stats.profit_trend.direction === 'up'
                            ? 'bg-white/20 text-white'
                            : 'bg-red-500/30 text-white border-red-400/30'
                    "
                >
                    <span v-if="stats.profit_trend.direction === 'up'">↗</span>
                    <span v-else>↘</span>
                    {{ Math.abs(stats.profit_trend.percent) }}%
                </span>

                <span class="text-xs font-medium text-lime-100 opacity-90">
                    {{ stats.profit_trend.direction === "up" ? "+" : "-" }}
                    {{ formatRupiah(Math.abs(stats.profit_trend.diff)) }}
                    <span class="opacity-70 text-[10px] ml-1"
                        >(vs Kemarin)</span
                    >
                </span>
            </div>

            <div
                class="flex justify-between mb-1 text-xs font-bold tracking-wide text-lime-50"
            >
                <span>Margin {{ stats.daily_margin }}%</span>
                <span class="opacity-80"
                    >Omzet: {{ formatRupiah(stats.revenue) }}</span
                >
            </div>

            <div
                class="w-full h-2.5 bg-black/20 rounded-full overflow-hidden mb-2 backdrop-blur-sm shadow-inner"
            >
                <div
                    class="h-full bg-white shadow-[0_0_10px_rgba(255,255,255,0.6)] transition-all duration-1000 ease-out relative"
                    :style="{ width: Math.min(100, stats.daily_margin) + '%' }"
                >
                    <div
                        class="absolute top-0 bottom-0 right-0 w-4 bg-white/50 blur-[2px]"
                    ></div>
                </div>
            </div>
        </div>

        <div class="relative z-10 px-6 pt-3 pb-6 border-t border-white/10">
            <div
                class="flex items-center justify-between text-xs font-medium text-lime-50"
            >
                <div class="flex items-center gap-1.5 opacity-90">
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        ></path>
                    </svg>
                    {{ stats.transactions }} Transaksi Selesai
                </div>
                <span
                    class="bg-black/10 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider backdrop-blur-sm border border-white/5"
                >
                    Live Data
                </span>
            </div>
        </div>
    </div>
</template>
