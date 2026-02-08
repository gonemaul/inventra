<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    links: Array,
});

// Helper untuk membersihkan label (hapus &laquo; &raquo;)
const cleanLabel = (label) => {
    return label.replace('&laquo; Previous', 'Prev').replace('Next &raquo;', 'Next');
};

// Filter links agar tidak terlalu panjang (Smart Pagination)
const visibleLinks = computed(() => {
    // Jika links tidak valid/kosong
    if (!props.links || props.links.length === 0) return [];

    const totalLinks = props.links.length;
    
    // Jika total halaman sedikit (< 10), tampilkan semua
    if (totalLinks < 10) return props.links;

    // Cari current page active index
    const activeIndex = props.links.findIndex(l => l.active);
    const currentPageInfo = props.links[activeIndex]; // Just in case needed
    
    // Kita akan menyusun array baru
    let output = [];
    
    // Always show Previous
    output.push(props.links[0]);

    // Always show Page 1
    output.push(props.links[1]);

    // Logic "..." di awal
    // Jika activeIndex jauh dari halaman 1 (misal ada di index 5 ke atas)
    // index 0=Prev, 1=Page1, 2=Page2... 
    // Jika aktif di page 5 (index 5), range kita 4,5,6. 
    // Indeks 2 (Page 2) tidak masuk. 
    if (activeIndex > 4) {
        output.push({ label: '...', url: null, active: false });
    }

    // Range sekitar Active Page (current - 1, current, current + 1)
    // Pastikan index valid dan bukan Page 1 atau Last Page (karena sudah di-handle khusus)
    for (let i = activeIndex - 1; i <= activeIndex + 1; i++) {
        // Skip jika index <= 1 (karena page 1 sudah masuk)
        // Skip jika index >= totalLinks - 2 (karena last page nanti masuk)
        if (i > 1 && i < totalLinks - 2) {
             output.push(props.links[i]);
        }
    }

    // Logic "..." di akhir
    if (activeIndex < totalLinks - 5) {
        output.push({ label: '...', url: null, active: false });
    }

    // Always show Last Page
    // Last page ada di index (totalLinks - 2) karena yang paling akhir adalah Next
    output.push(props.links[totalLinks - 2]);

    // Always show Next
    output.push(props.links[totalLinks - 1]);

    // Hapus duplikat (misal jika range overlap dengan page 1 atau last page)
    // Karena kita push manual, bisa jadi ada duplikat object reference atau logic overlap
    // Cara mudah: Filter unique by label (Asumsi label unik)
    const unique = [];
    const labels = new Set();
    for(const l of output) {
        if(!labels.has(l.label)) {
            unique.push(l);
            labels.add(l.label);
        }
    }
    
    return unique;
});

</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap items-center justify-center gap-1 mt-4">
        <template v-for="(link, key) in visibleLinks" :key="key">
            <div v-if="link.url === null" class="px-3 py-1 text-sm text-gray-500 border rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700" v-html="cleanLabel(link.label)" />
            
            <Link
                v-else
                :href="link.url"
                class="px-3 py-1 text-sm border rounded transition-colors duration-150"
                :class="{
                    'bg-blue-600 text-white border-blue-600 font-bold shadow-sm': link.active,
                    'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700': !link.active
                }"
                v-html="cleanLabel(link.label)"
            />
        </template>
    </div>
</template>
