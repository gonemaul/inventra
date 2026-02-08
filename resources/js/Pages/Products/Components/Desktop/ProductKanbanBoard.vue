<script setup>
import { computed } from "vue";
import ProductKanbanCard from "./ProductKanbanCard.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    products: Array, // Data raw dari backend
});

const groupedByCategory = computed(() => {
    const groups = {};
    
    // 1. Grouping
    props.products.forEach(product => {
        const categoryName = product.category?.name || 'Uncategorized';
        if (!groups[categoryName]) {
            groups[categoryName] = {
                name: categoryName,
                products: []
            };
        }
        groups[categoryName].products.push(product);
    });

    // 2. Sort Category by Name (Optional) and return as array
    return Object.values(groups).sort((a, b) => a.name.localeCompare(b.name));
});

const openDetail = (slug) => {
    router.visit(route("products.show", slug));
};
</script>

<template>
    <div class="pb-4 overflow-x-auto">
        <div class="flex gap-4 min-w-full w-max px-1 pt-1 pb-4">
            <div
                v-for="group in groupedByCategory"
                :key="group.name"
                class="w-[280px] flex-shrink-0 flex flex-col max-h-[75vh] bg-gray-100/80 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 backdrop-blur-sm"
            >
                <!-- Header Column -->
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-gray-100/90 dark:bg-gray-800/90 z-10 rounded-t-xl backdrop-blur-md">
                    <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm truncate uppercase tracking-wider">
                        {{ group.name }}
                    </h3>
                    <span class="bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-mono text-xs px-2 py-0.5 rounded-md border border-gray-200 dark:border-gray-600 shadow-sm">
                        {{ group.products.length }}
                    </span>
                </div>

                <!-- Product List -->
                <div class="p-3 flex flex-col gap-3 overflow-y-auto custom-scrollbar flex-1">
                    <ProductKanbanCard
                        v-for="p in group.products"
                        :key="p.id"
                        :data="p"
                        @click="openDetail(p.slug)"
                    />
                </div>
            </div>
            
            <!-- Empty State Helper -->
             <div v-if="products.length === 0" class="flex flex-col items-center justify-center w-full py-20 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <p>Belum ada data produk untuk ditampilkan.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar halus untuk isi kolom */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
