<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

// 1. Menerima Data dari Parent
const props = defineProps({
    // Data Utama
    categories: { type: Array, required: true },
    brands: { type: Array, default: () => [] },
    isFetching: { type: Boolean, default: false }, // Status loading sync

    // State Filter (v-model bindings)
    search: { type: String, default: "" }, // v-model:search
    category: { type: [String, Number], default: "all" }, // v-model:category
    subCategory: { type: [String, Number], default: "all" }, // v-model:subCategory
    brand: { type: [String, Number], default: "all" }, // v-model:brand
    sort: { type: String, default: "default" }, // v-model:sort
    hideEmptyStock: { type: Boolean, default: false }, // v-model:hideEmptyStock
});

// 2. Mengirim Event ke Parent
const emit = defineEmits([
    "update:search",
    "update:category",
    "update:subCategory",
    "update:brand",
    "update:sort",
    "update:hideEmptyStock",
    "scan", // Event tombol scan
]);

// Helper: Hitung Sub Kategori berdasarkan Kategori yang dipilih
const activeSubCategories = computed(() => {
    if (props.category === "all") return [];
    // Fix: Gunakan '==' untuk comparison yang aman terhadap number/string difference
    const cat = props.categories.find((c) => c.id == props.category);
    return cat ? cat.product_types || [] : []; // Pastikan backend kirim 'subs' atau sesuaikan key-nya
});

// Helper: Fungsi update agar template lebih bersih
const updateCategory = (val) => {
    emit("update:category", val);
    emit("update:subCategory", "all"); // Reset sub saat ganti kategori utama
    emit("update:sort", "default"); // Reset sort (opsional)
};
</script>

<template>
    <div
        class="z-30 flex flex-col border-b shadow-sm bg-gray-50 dark:bg-gray-900 dark:border-gray-700"
    >
        <div
            class="flex items-center gap-3 px-4 py-3 bg-white border-b dark:bg-gray-800 dark:border-gray-700"
        >
            <Link
                :href="route('dashboard')"
                class="p-2 rounded-lg bg-lime-50 dark:bg-gray-700 text-lime-700 dark:text-lime-400"
            >
                <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    ></path>
                </svg>
            </Link>

            <div class="relative flex-1">
                <input
                    :value="search"
                    @input="$emit('update:search', $event.target.value)"
                    type="text"
                    placeholder="Cari Nama / Kode..."
                    class="w-full pl-9 pr-10 py-2.5 bg-gray-100 dark:bg-gray-900 border-none rounded-xl focus:ring-2 focus:ring-lime-500 text-sm dark:text-white transition-all shadow-inner"
                />
                <span class="absolute text-gray-400 left-3 top-3">🔍</span>

                <div
                    v-if="isFetching"
                    class="absolute right-11 top-3.5 flex items-center gap-2"
                >
                    <span class="text-[10px] text-gray-400 italic"
                        >Syncing...</span
                    >
                    <span class="relative flex w-2 h-2">
                        <span
                            class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-lime-400"
                        ></span>
                        <span
                            class="relative inline-flex w-2 h-2 rounded-full bg-lime-500"
                        ></span>
                    </span>
                </div>

                <button
                    @click="$emit('scan')"
                    class="absolute right-1.5 top-1.5 p-1 bg-white dark:bg-gray-700 rounded-lg shadow-sm text-gray-600 hover:text-lime-600 hover:bg-lime-50 border border-gray-200 dark:border-gray-600 transition"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                        ></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <div
            class="px-4 py-2 overflow-x-auto bg-white border-b dark:bg-gray-800 dark:border-gray-700 whitespace-nowrap scrollbar-hide"
        >
            <button
                @click="updateCategory('all')"
                :class="
                    category === 'all'
                        ? 'bg-lime-500 text-white shadow-lime-500/30'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                "
                class="px-5 py-2 mr-2 text-xs font-bold transition-all rounded-full active:scale-95"
            >
                Semua
            </button>
            <button
                v-for="cat in categories"
                :key="cat.id"
                @click="updateCategory(cat.id)"
                :class="
                    category === cat.id
                        ? 'bg-lime-500 text-white shadow-lime-500/30'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                "
                class="px-5 py-2 mr-2 text-xs font-bold transition-all rounded-full active:scale-95"
            >
                {{ cat.name }}
            </button>
        </div>

        <div
            class="flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-900 shrink-0 max-w-screen"
        >
         <!-- Brand Filter -->
          <div class="flex gap-2">
              <div class="flex flex-col items-left gap-2 shrink-0">
                  <span class="text-[10px] font-bold text-gray-400 uppercase">Brand:</span>
                  <select
                      :value="brand"
                      @change="$emit('update:brand', $event.target.value)"
                      class="py-1 pl-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:ring-lime-500 focus:border-lime-500"
                  >
                      <option value="all">Semua</option>
                      <option v-for="b in brands" :key="b.id" :value="b.id">
                          {{ b.name }}
                      </option>
                  </select>
              </div>
              <div class="flex flex-col items-left gap-2 shrink-0" v-if="activeSubCategories?.length > 0">
                  <span class="text-[10px] font-bold text-gray-400 uppercase">Tipe:</span>
                  <select
                      :value="subCategory"
                      @change="$emit('update:subCategory', $event.target.value)"
                      class="py-1 pl-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:ring-lime-500 focus:border-lime-500"
                  >
                      <option value="all">Semua</option>
                      <option v-for="sub in activeSubCategories" :key="sub.id" :value="sub.id">
                          {{ sub.name }}
                      </option>
                  </select>
              </div>
          </div>
            <div
                class="flex items-center mt-auto gap-2 shrink-0"
            >
                <button
                    @click="$emit('update:hideEmptyStock', !hideEmptyStock)"
                    :class="
                        hideEmptyStock
                            ? 'bg-red-100 text-red-700 border-red-200'
                            : 'bg-white text-gray-400 border-gray-200'
                    "
                    class="flex items-center gap-1.5 px-2.5 py-1.5 border rounded-lg transition-all"
                    title="Sembunyikan Stok Kosong"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M17.94 17.94A10 10 0 0 1 12 20c-5.52 0-10-4.48-10-10a9.93 9.93 0 0 1 2.06-6.06" />
                        <path d="M12 2a10 10 0 0 1 7.94 4.06" />
                        <line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                    <span class="text-xs font-bold hidden sm:inline">Stok > 0</span>
                </button>

                <button
                    @click="
                        $emit(
                            'update:sort',
                            sort === 'cheapest' ? 'default' : 'cheapest'
                        )
                    "
                    :class="
                        sort === 'cheapest'
                            ? 'bg-orange-100 text-orange-700 border-orange-200'
                            : 'bg-white text-gray-400 border-gray-200'
                    "
                    class="p-1.5 border rounded-lg transition-all"
                    title="Termurah"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 20V10" />
                        <path d="M18 20V4" />
                        <path d="M6 20v-4" />
                    </svg>
                </button>
                <button
                    @click="
                        $emit(
                            'update:sort',
                            sort === 'bestseller' ? 'default' : 'bestseller'
                        )
                    "
                    :class="
                        sort === 'bestseller'
                            ? 'bg-purple-100 text-purple-700 border-purple-200'
                            : 'bg-white text-gray-400 border-gray-200'
                    "
                    class="p-1.5 border rounded-lg transition-all"
                    title="Terlaris"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                        <path d="M4 22h16" />
                        <path
                            d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"
                        />
                        <path
                            d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"
                        />
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
