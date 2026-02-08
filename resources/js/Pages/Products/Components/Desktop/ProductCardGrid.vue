<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
// Anak View

const props = defineProps({
    data: Object,
});

const emit = defineEmits([
    "imageClick",
    "delete",
    "restore",
    "forceDelete",
    "adjustStock",
    "adjustPrice",
]);
const showOptions = ref(false);
// Helper
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const isTrashed = computed(() => props.data.deleted_at !== null);
</script>
<template>
    <div
        :class="
            data.stock == 0
                ? 'ring-2 ring-red-500/30'
                : 'hover:border-lime-500/50'
        "
        class="group relative flex flex-col h-full bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-2xl transition-all duration-300 ease-[cubic-bezier(0.23,1,0.32,1)] hover:-translate-y-1 overflow-hidden"
        @mouseleave="showOptions = false"
    >
        <!-- Image Container: 1:1 Aspect Ratio -->
        <div
            class="relative w-full aspect-square overflow-hidden bg-gray-50 dark:bg-gray-900 cursor-pointer"
            @click="
                emit('imageClick', { path: data.image_url, name: data.name })
            "
        >
             <!-- Placeholder Icon -->
            <div
                class="absolute inset-0 flex flex-col items-center justify-center text-gray-300 transition-opacity duration-300"
            >
                 <svg
                    class="w-12 h-12 opacity-30"
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

            <!-- Image -->
            <img
                :src="data.image_url"
                :alt="data.name"
                loading="lazy"
                class="absolute inset-0 object-cover w-full h-full transition-transform duration-700 ease-out opacity-0 group-hover:scale-110"
                onload="this.classList.remove('opacity-0')"
                onerror="this.style.display='none'"
            />
            
            <!-- Overlay Gradient (On Hover only or Always subtle?) -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

            <!-- Stock Status (Empty) -->
            <div
                v-if="data.stock <= 0"
                class="absolute inset-0 z-20 flex items-center justify-center bg-gray-900/40 backdrop-blur-sm"
            >
                <span
                    class="px-3 py-1 text-sm font-bold tracking-widest text-white uppercase border-2 border-white rounded transform -rotate-12 bg-red-600/90 shadow-lg"
                    >Stok Habis</span
                >
            </div>

            <!-- Badges (Top Left) -->
            <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10 transition-transform duration-300 group-hover:translate-x-1">
                <span
                    v-for="badge in data.active_badges"
                    :key="badge.type"
                    :class="[
                        'self-start px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-white rounded-md shadow-md backdrop-blur-sm',
                        badge.class,
                    ]"
                >
                    {{ badge.label }}
                </span>
            </div>

            <!-- Supplier Badge (Bottom Left) -->
            <!-- <span
                class="absolute z-20 bottom-3 left-3 px-2 py-1 text-[9px] font-bold uppercase tracking-wider bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-200 rounded-md shadow-sm backdrop-blur-sm"
                >{{ data.supplier.name }}</span
            > -->

            <!-- Active Status Dot -->
             <div class="absolute top-3 right-3 z-10">
                <span
                    class="block w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-gray-800 shadow-sm"
                    :class="
                        data.status === 'active'
                            ? 'bg-green-500'
                            : 'bg-gray-400'
                    "
                >
                </span>
            </div>
        </div>

        <!-- Content Info -->
        <div class="flex flex-col flex-1 p-5 gap-3 bg-gradient-to-b from-white/90 to-white dark:from-gray-800/90 dark:to-gray-800">
             <!-- Brand & Category Meta -->
             <div class="flex items-center gap-2 text-[10px] font-bold tracking-wider uppercase text-gray-400">
                <span class="text-lime-600 dark:text-lime-400 truncate max-w-[50%]">
                    {{ data.brand?.name }}
                </span>
                <span class="text-gray-300">•</span>
                <span class="truncate max-w-[50%]">
                    {{ data.category?.name }}
                </span>
             </div>

            <!-- Product Title -->
            <Link
                :href="route('products.show', data.slug)"
                class="block text-base font-bold leading-tight text-gray-800 transition-colors dark:text-white hover:text-lime-600 dark:hover:text-lime-400 line-clamp-2 min-h-[3rem]"
                :title="data.name"
            >
                {{ data.name }}
            </Link>
            
            <!-- Code & Stock Grid -->
            <div class="grid grid-cols-2 gap-2 mt-auto">
                 <div class="px-2 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex flex-col">
                     <span class="text-[9px] uppercase font-bold text-gray-400">Kode</span>
                     <span class="font-mono truncate font-semibold">{{ data.code }}</span>
                 </div>
                 <div 
                    class="px-2 py-1.5 rounded-lg border flex flex-col"
                    :class="data.stock <= data.min_stock ? 'bg-red-50 border-red-100 text-red-600' : 'bg-gray-50 dark:bg-gray-700/50 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-300'"
                 >
                     <span class="text-[9px] uppercase font-bold opacity-70">Stok</span>
                     <span class="font-bold text-xs">{{ data.stock }} {{ data.unit?.name }}</span>
                 </div>
            </div>

            <!-- Divider -->
            <div class="h-px bg-gray-100 dark:bg-gray-700 my-1"></div>

            <!-- Price Section & Actions -->
            <div class="flex items-center justify-between">
                <div>
                     <!-- Margin Info -->
                    <!-- <div class="text-[10px] font-medium text-gray-400 mb-0.5 flex items-center gap-1">
                        Margin 
                        <span :class="data.is_margin_low ? 'text-red-500 font-bold' : 'text-green-600 font-bold'">
                             {{ data.current_margin["percent"] }}%
                        </span>
                    </div> -->
                    <div class="text-lg font-black text-gray-900 dark:text-white">
                        {{ formatRupiah(data.selling_price) }}
                    </div>
                </div>

                <!-- Action Toggle -->
                <div class="relative">
                    <button
                        @click.stop="showOptions = !showOptions"
                        class="p-2 transition-colors rounded-full text-gray-400 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white"
                         :class="{ 'bg-gray-100 dark:bg-gray-700 text-gray-800': showOptions }"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                            />
                        </svg>
                    </button>

                    <!-- Popover Menu -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="transform scale-95 opacity-0 translate-y-2"
                        enter-to-class="transform scale-100 opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="transform scale-100 opacity-100 translate-y-0"
                        leave-to-class="transform scale-95 opacity-0 translate-y-2"
                    >
                        <div
                            v-if="showOptions"
                            class="absolute bottom-full right-0 mb-2 z-50 p-1.5 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-600 flex flex-col gap-1 w-max min-w-[140px]"
                            @click.stop
                        >
                             <!-- Edit Header -->
                             <div class="px-2 py-1 text-[10px] font-bold uppercase text-gray-400 border-b border-gray-100 dark:border-gray-700 mb-1">
                                 Menu Aksi
                             </div>

                             <!-- Quick Actions -->
                             <div class="grid grid-cols-2 gap-1 mb-1" v-if="!isTrashed">
                                 <button
                                    @click="emit('adjustPrice', data); showOptions = false;"
                                    title="Atur Harga"
                                    class="flex items-center justify-center p-2 text-orange-600 rounded-lg bg-orange-50 hover:bg-orange-100 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <button
                                    @click="emit('adjustStock', data); showOptions = false;"
                                    title="Atur Stok"
                                    class="flex items-center justify-center p-2 text-blue-600 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </button>
                             </div>

                            <Link
                                v-if="!isTrashed"
                                :href="route('products.edit', data.slug)"
                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors"
                            >
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Edit Produk
                            </Link>
                            
                            <button
                                v-if="!isTrashed"
                                @click="emit('delete', data)"
                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            >
                                <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Hapus
                            </button>

                             <!-- Restore / Force Delete for Trash -->
                            <button
                                v-if="isTrashed"
                                @click="emit('restore', data)"
                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-lime-600 rounded-lg hover:bg-lime-50"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Pulihkan
                            </button>
                            <button
                                v-if="isTrashed"
                                @click="emit('forceDelete', data)"
                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-600 rounded-lg hover:bg-red-50"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Hapus Permanen
                            </button>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </div>
</template>
