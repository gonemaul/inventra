<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";

const props = defineProps({
    data: Object,
    dss: Object,
    price_trend: Object,
});
const showConfirmModal = ref(null);
const openDeleteModal = (product, isPermanent = false) => {
    let config = {};
    if (isPermanent) {
        config = {
            title: "Hapus Permanen Produk",
            message: "Produk ini akan dihapus selamanya. Anda yakin menghapus",
            itemName: product.name,
            url: route("products.destroy", {
                id: product.slug,
                permanen: true,
            }),
        };
    } else {
        config = {
            title: "Pindahkan ke Sampah",
            message: "Anda yakin ingin memindahkan produk",
            itemName: product.name,
            url: route("products.destroy", { id: product.slug }),
        };
    }
    showConfirmModal.value.open(config);
};
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const isStockLow = computed(() => {
    const hasInsight = props.data.insights?.some(
        (i) => i.type === "restock" && i.severity === "critical"
    );
    return hasInsight || props.data.stock <= props.data.min_stock;
});
</script>

<template>
    <DeleteConfirm ref="showConfirmModal" @success="" />
    <div
        class="flex flex-col overflow-hidden bg-white border border-gray-200 shadow-sm md:flex-row dark:bg-gray-800 rounded-2xl dark:border-gray-700"
    >
        <!-- Left: Image Section -->
        <div
            class="relative w-full md:w-80 lg:w-96 shrink-0 bg-gray-50 dark:bg-gray-900 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 group overflow-hidden"
        >
            <!-- Image Container (Square Aspect Ratio) -->
            <div class="relative w-full aspect-square flex items-center justify-center p-6">
                <!-- Placeholder / Background -->
                 <div
                    class="absolute inset-0 z-0 flex flex-col items-center justify-center text-gray-300 dark:text-gray-700"
                >
                    <svg
                        class="w-16 h-16 opacity-50"
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
                </div>

                <img
                    :src="data.image_url"
                    :alt="data.name"
                    loading="lazy"
                    decoding="async"
                    onload="this.classList.remove('opacity-0')"
                    onerror="this.style.display='none'"
                    class="relative z-10 object-contain w-full h-full transition-transform duration-500 opacity-0 cursor-zoom-in group-hover:scale-105"
                    @click="
                        $emit('imageClick', {
                            path: data.image_url,
                            name: data.name,
                        })
                    "
                />

                <!-- Badges Overlay -->
                <div class="absolute top-4 left-4 z-20 flex flex-col gap-2">
                     <span
                        v-if="dss?.is_trending"
                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white bg-purple-600 shadow-lg shadow-purple-500/30 rounded-lg backdrop-blur-md flex items-center gap-1.5"
                    >
                        🔥 TRENDING
                    </span>
                    <span
                        v-if="dss?.is_dead_stock"
                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white bg-gray-600 shadow-lg shadow-gray-500/30 rounded-lg backdrop-blur-md flex items-center gap-1.5"
                    >
                        🐢 SLOW MOVING
                    </span>
                     <span
                        v-if="isStockLow"
                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white bg-red-600 shadow-lg shadow-red-500/30 rounded-lg backdrop-blur-md flex items-center gap-1.5"
                    >
                        🚨 STOK MENIPIS
                    </span>
                </div>
            </div>

            <!-- Status Bar (Bottom of image) -->
             <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/50 to-transparent z-20 flex justify-between items-end">
                <div 
                    class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider backdrop-blur-md border border-white/20 shadow-sm"
                    :class="data.status === 'active' ? 'bg-green-500/90 text-white' : 'bg-gray-500/90 text-white'"
                >
                    {{ data.status === 'active' ? '● Aktif' : '○ Non-Aktif' }}
                </div>
                 <div v-if="data.supplier" class="px-2 py-1 rounded-md text-[10px] font-bold text-white bg-black/40 backdrop-blur-md border border-white/10 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    {{ data.supplier?.name }}
                 </div>
            </div>
        </div>

        <!-- Right: Info Section -->
        <div class="flex flex-col justify-between flex-1 p-6 md:p-8 space-y-6">
            <div>
                <!-- Header Info -->
                <div class="flex flex-wrap items-center gap-3 mb-4 text-xs">
                     <span class="px-2 py-1 font-bold text-lime-700 bg-lime-100 rounded-md dark:bg-lime-900/30 dark:text-lime-400 uppercase tracking-wide">
                        {{ data.brand?.name || "No Brand" }}
                    </span>
                    <span class="text-gray-300 dark:text-gray-600">/</span>
                    <span class="font-medium text-gray-500 dark:text-gray-400">
                        {{ data.category?.name }}
                    </span>
                     <span class="text-gray-300 dark:text-gray-600">/</span>
                    <span class="font-mono text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
                        {{ data.code }}
                    </span>
                </div>

                <!-- Title -->
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white leading-tight mb-6">
                    {{ data.full_name || data.name }}
                </h1>

                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pb-6 border-b border-gray-100 dark:border-gray-700">
                     <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
                        <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Stok Saat Ini</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-xl font-bold text-gray-900 dark:text-white" :class="{'text-red-500': isStockLow}">{{ data.stock }}</span>
                            <span class="text-xs text-gray-500">{{ data.unit?.name }}</span>
                        </div>
                     </div>
                     <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
                        <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Ukuran/Size</span>
                        <span class="text-base font-bold text-gray-900 dark:text-white">{{ data.size?.name || "-" }}</span>
                     </div>
                     <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 col-span-2">
                         <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Status Keuangan</span>
                         <div class="flex items-center gap-3">
                             <div>
                                 <span class="text-[10px] text-gray-400">Beli:</span>
                                 <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 block">{{ formatRupiah(data.purchase_price) }}</span>
                             </div>
                             <div class="w-px h-6 bg-gray-200 dark:bg-gray-600"></div>
                             <div>
                                 <span class="text-[10px] text-gray-400">Margin:</span>
                                 <span class="text-sm font-bold text-green-600 block">+{{ data.financials.margin.percent }}%</span>
                             </div>
                         </div>
                     </div>
                </div>
            </div>

            <!-- Price & Actions Footer -->
             <div class="space-y-6">
                 <!-- Main Price -->
                 <div class="flex flex-wrap items-end justify-between gap-4">
                     <div>
                         <span class="text-sm font-medium text-gray-400 block mb-1">Harga Jual</span>
                         <div class="text-4xl font-black text-lime-600 dark:text-lime-400 tracking-tight">
                             {{ formatRupiah(data.selling_price) }}
                         </div>
                     </div>
                     
                     <!-- Price Trend -->
                      <div v-if="data.financials?.price_trend?.direction !== 'stable'" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                          <span class="text-lg">{{ data.financials.price_trend.direction === 'up' ? '📈' : '📉' }}</span>
                          <div class="flex flex-col leading-none">
                              <span class="text-[10px] font-bold uppercase text-gray-400">Trend Harga</span>
                              <span class="text-xs font-bold" :class="data.financials.price_trend.direction === 'up' ? 'text-red-500' : 'text-green-500'">
                                  {{ data.financials.price_trend.direction === 'up' ? 'Naik' : 'Turun' }} {{ data.financials.price_trend.percent }}%
                              </span>
                          </div>
                      </div>
                 </div>

                 <!-- Action Buttons -->
                 <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <Link
                        :href="route('products.index')"
                        class="flex-1 px-6 py-3 text-sm font-bold text-center text-gray-600 bg-gray-200 border border-gray-200 rounded-xl hover:bg-gray-300 hover:text-gray-900 transition-colors"
                    >
                        Kembali
                    </Link>
                    <Link
                        :href="route('products.edit', data.slug)"
                        class="flex-1 px-6 py-3 text-sm font-bold text-center text-white bg-yellow-500 rounded-xl hover:bg-yellow-600 shadow-lg shadow-yellow-500/30 transition-colors flex items-center justify-center gap-2"
                    >
                        <i class="fa-solid fa-pen-to-square"></i> Edit Produk
                    </Link>
                    <button
                        @click="openDeleteModal(data)"
                        class="flex-1 px-6 py-3 text-sm font-bold text-center text-red-600 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-colors flex items-center justify-center gap-2"
                    >
                        <i class="fa-regular fa-trash-can"></i> Hapus
                    </button>
                 </div>
             </div>
        </div>
    </div>
</template>
