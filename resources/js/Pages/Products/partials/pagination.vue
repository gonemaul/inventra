<script setup>
import { Link, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const props = defineProps({
    metadata: Object,
    filters: Object,
});

const pageSizeOptions = [10, 25, 50, 100];
const perPage = ref(props.metadata.per_page);

watch(perPage, (newValue) => {
    let allFilters = { ...props.filters };
    allFilters.per_page = newValue;
    allFilters.page = 1;
    router.get(route("products.index"), allFilters, {
        preserveState: true,
        replace: true,
    });
});

// Smart Pagination Logic
const visibleLinks = computed(() => {
    const { current_page, last_page, links } = props.metadata;
    // links[0] is Previous, links[links.length-1] is Next
    
    // Jika total halaman sedikit, tampilkan semua (misal < 7)
    if (last_page < 7) {
        return links;
    }

    const filtered = [];
    
    // Selalu masukkan Previous (index 0)
    filtered.push(links[0]);

    // Selalu masukkan Halaman 1
    filtered.push(links[1]);

    // Logic untuk "..."
    if (current_page > 4) {
        filtered.push({ label: '...', url: null, active: false });
    }

    // Ambil range sekitar current page
    // Kita ambil link dari array asli. Ingat index array links:
    // Page 1 ada di index 1. Page N ada di index N.
    // Jadi range start dan end harus disesuaikan.
    
    let start = Math.max(2, current_page - 1);
    let end = Math.min(last_page - 1, current_page + 1);

    if (current_page <= 3) {
        end = 4; // Pastikan minimal tampil sampai halaman 4 jika di awal
    }
    
    if (current_page >= last_page - 2) {
        start = last_page - 3; // Pastikan tampil mundur jika di akhir
    }

    for (let i = start; i <= end; i++) {
        // Cari link yang label-nya sama dengan i (string/number)
        // Karena struktur links Laravel index-nya bisa bergeser jika ada '...' bawaan (jarang di default JSON)
        // Tapi amannya kita akses by index jika standar: index = i
        // Atau find.
        const link = links[i]; 
        if(link) filtered.push(link);
    }

    if (current_page < last_page - 3) {
        filtered.push({ label: '...', url: null, active: false });
    }

    // Selalu masukkan Halaman Terakhir (index last_page) 
    // Note: links array size = last_page + 2 (prev + next)
    filtered.push(links[links.length - 2]);

    // Selalu masukkan Next (index terakhir)
    filtered.push(links[links.length - 1]);

    return filtered;
});

// Helper untuk membersihkan label (hapus &laquo; &raquo;)
const cleanLabel = (label) => {
    return label.replace('&laquo; Previous', 'Prev').replace('Next &raquo;', 'Next');
};
</script>

<template>
    <div
        class="flex flex-col items-center justify-between gap-4 mt-6 md:flex-row"
    >
        <!-- Page size -->
        <div class="flex items-center gap-2">
            <label
                for="pageSize"
                class="text-sm text-gray-600 dark:text-gray-200"
                >Tampilkan:</label
            >
            <select
                id="pageSize"
                v-model="perPage"
                class="px-2 py-1 text-sm border-2 border-gray-500 rounded-md w-15 dark:bg-gray-700 dark:text-white"
            >
                <option
                    v-for="size in pageSizeOptions"
                    :key="size"
                    :value="size"
                >
                    {{ size }}
                </option>
            </select>
        </div>
        
        <!-- Info jumlah data (Hidden on small mobile to save space if needed, or styled smaller) -->
        <div class="text-xs text-center text-gray-600 md:text-sm dark:text-gray-200">
            Menampilkan
            <span class="font-semibold">{{ metadata.from ?? 0 }}</span> -
            <span class="font-semibold">{{ metadata.to ?? 0 }}</span> dari
            <span class="font-semibold">{{ metadata.total }}</span> data
        </div>

        <!-- Pagination -->
        <div class="flex flex-wrap items-center justify-center gap-1">
            <template v-for="(link, key) in visibleLinks" :key="key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1 text-sm border rounded-md transition-colors"
                    :class="{
                        'bg-lime-600 text-white border-lime-600': link.active,
                        'bg-white text-gray-700 border-gray-300 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700': !link.active,
                    }"
                    v-html="cleanLabel(link.label)"
                ></Link>
                <span
                    v-else
                    class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-default dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500"
                    v-html="cleanLabel(link.label)"
                ></span>
            </template>
        </div>
    </div>
</template>
