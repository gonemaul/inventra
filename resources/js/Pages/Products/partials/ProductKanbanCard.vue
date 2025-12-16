<script setup>
import { computed } from "vue";

const props = defineProps({
    data: Object,
    type: String, // 'critical', 'trending', 'dead', 'safe'
});

const emit = defineEmits(["click"]);

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Logic Data Tambahan (Agar template bersih)
const insightData = computed(() => {
    // Ambil data spesifik dari payload insight jika ada
    if (props.type === "trending") {
        const insight = props.data.insights?.find((i) => i.type === "trend");
        return insight?.payload || null;
    }
    if (props.type === "dead") {
        const insight = props.data.insights?.find(
            (i) => i.type === "dead_stock"
        );
        return insight?.payload || null;
    }
    return null;
});
</script>

<template>
    <div
        class="group relative flex w-full bg-white dark:bg-gray-800 p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-lime-400 transition-all cursor-pointer"
        @click="emit('click')"
    >
        <div
            class="relative flex-shrink-0 w-16 h-16 overflow-hidden bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 group"
        >
            <div
                class="absolute inset-0 z-0 flex flex-col items-center justify-center w-full h-full transition-colors bg-gray-100 dark:bg-gray-800"
            >
                <svg
                    class="w-5 h-5 text-gray-400 dark:text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    ></path>
                </svg>
                <span
                    class="text-[8px] font-bold text-gray-400 dark:text-gray-500 mt-0.5"
                    >NO IMG</span
                >
            </div>

            <img
                :src="data.image_url"
                loading="lazy"
                decoding="async"
                onload="this.classList.remove('opacity-0')"
                onerror="this.style.display='none'"
                class="absolute inset-0 z-10 object-contain w-full h-full p-1 transition-transform duration-300 bg-white opacity-0 dark:bg-gray-800 group-hover:scale-110"
            />

            <div
                class="absolute top-1 left-1 z-20 w-2.5 h-2.5 rounded-full border border-white shadow-sm ring-1 ring-black/5"
                :class="
                    data.status === 'active' ? 'bg-green-500' : 'bg-gray-400'
                "
            ></div>
        </div>

        <div class="flex flex-col justify-between flex-1 min-w-0 ml-3">
            <div>
                <div class="flex items-start justify-between">
                    <span
                        class="text-[9px] font-mono text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-1 rounded truncate max-w-[80px]"
                    >
                        {{ data.code }}
                    </span>
                    <span
                        class="text-[9px] text-gray-400 font-bold uppercase truncate max-w-[60px]"
                    >
                        {{ data.category?.name }}
                    </span>
                </div>

                <h4
                    class="text-xs font-bold text-gray-800 dark:text-white leading-tight mt-0.5 line-clamp-1 group-hover:text-lime-600 transition"
                >
                    {{ data.name }}
                </h4>
            </div>

            <div class="flex items-end justify-between mt-2">
                <div class="text-[10px] leading-none">
                    <div v-if="type === 'critical'" class="flex flex-col">
                        <span class="text-gray-400 text-[9px]">Sisa Stok</span>
                        <span
                            class="flex items-center gap-1 font-bold text-red-600"
                        >
                            🚨 {{ data.stock }} {{ data.unit?.name }}
                        </span>
                    </div>

                    <div v-else-if="type === 'trending'" class="flex flex-col">
                        <span class="text-gray-400 text-[9px]">Growth</span>
                        <span
                            class="flex items-center gap-1 font-bold text-purple-600"
                        >
                            🔥 +{{ insightData?.growth_percent || "?" }}%
                        </span>
                    </div>

                    <div v-else-if="type === 'dead'" class="flex flex-col">
                        <span class="text-gray-400 text-[9px]">Macet</span>
                        <span
                            class="flex items-center gap-1 font-bold text-gray-600"
                        >
                            🐢 {{ insightData?.days_inactive || ">90" }} Hari
                        </span>
                    </div>

                    <div v-else class="flex flex-col">
                        <span class="text-gray-400 text-[9px]">Stok Aman</span>
                        <span
                            class="flex items-center gap-1 font-bold text-green-600"
                        >
                            ✅ {{ data.stock }} {{ data.unit?.name }}
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <div
                        class="text-xs font-black text-gray-900 dark:text-white"
                    >
                        {{ formatRupiah(data.selling_price) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
