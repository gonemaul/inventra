<script setup>
import { computed } from "vue";
import ProductKanbanCard from "./ProductKanbanCard.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    products: Array, // Data raw dari backend
});

// LOGIC PENGELOMPOKAN (ALLOW DUPLICATES)
// Satu produk bisa masuk ke Critical DAN Trending sekaligus.
// Hanya masuk ke 'Safe' jika tidak punya masalah sama sekali.

const grouped = computed(() => {
    const groups = {
        critical: [],
        trending: [],
        dead: [],
        safe: [],
    };

    props.products.forEach((product) => {
        const insights = product.insights || [];
        let hasIssue = false; // Penanda apakah produk ini punya status khusus
        // 1. Cek Restock (Independen)
        if (
            insights.some(
                (i) => i.type === "restock" && i.severity === "critical"
            ) ||
            product.stock <= product.min_stock
        ) {
            groups.critical.push(product);
            hasIssue = true;
        }

        // 2. Cek Trending (Independen - Pakai IF baru, bukan ELSE IF)
        if (insights.some((i) => i.type === "trend")) {
            groups.trending.push(product);
            hasIssue = true;
        }

        // 3. Cek Dead Stock (Independen)
        if (insights.some((i) => i.type === "dead_stock")) {
            groups.dead.push(product);
            hasIssue = true;
        }

        // 4. Cek Safe (Hanya jika TIDAK punya isu di atas)
        if (!hasIssue) {
            groups.safe.push(product);
        }
    });

    return groups;
});

const openDetail = (id) => {
    router.visit(route("products.show", id));
};
</script>

<template>
    <div class="pb-4 overflow-x-auto">
        <div class="flex gap-4 min-w-[1000px] max-h-[55vh] lg:min-w-0">
            <div
                class="flex-1 bg-red-50/50 dark:bg-red-900/10 rounded-xl p-3 border border-red-100 dark:border-red-800 flex flex-col gap-3 min-w-[250px]"
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-red-200"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-red-700"
                    >
                        🚨 Perlu Restock
                        <span
                            class="bg-red-200 text-red-800 px-1.5 rounded-full text-[10px]"
                            >{{ grouped.critical.length }}</span
                        >
                    </h3>
                </div>
                <div
                    class="flex flex-col gap-2 overflow-y-auto max-h-[600px] pr-1 custom-scrollbar"
                >
                    <ProductKanbanCard
                        v-for="p in grouped.critical"
                        :key="p.id"
                        :data="p"
                        type="critical"
                        @click="openDetail(p.id)"
                    />
                    <div
                        v-if="grouped.critical.length === 0"
                        class="py-8 text-xs italic text-center text-red-300"
                    >
                        Stok aman bos!
                    </div>
                </div>
            </div>

            <div
                class="flex-1 bg-purple-50/50 dark:bg-purple-900/10 rounded-xl p-3 border border-purple-100 dark:border-purple-800 flex flex-col gap-3 min-w-[250px]"
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-purple-200"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-purple-700"
                    >
                        🔥 Sedang Hype
                        <span
                            class="bg-purple-200 text-purple-800 px-1.5 rounded-full text-[10px]"
                            >{{ grouped.trending.length }}</span
                        >
                    </h3>
                </div>
                <div
                    class="flex flex-col gap-2 overflow-y-auto max-h-[600px] pr-1 custom-scrollbar"
                >
                    <ProductKanbanCard
                        v-for="p in grouped.trending"
                        :key="p.id"
                        :data="p"
                        type="trending"
                        @click="openDetail(p.id)"
                    />
                    <div
                        v-if="grouped.trending.length === 0"
                        class="py-8 text-xs italic text-center text-purple-300"
                    >
                        Belum ada tren
                    </div>
                </div>
            </div>

            <div
                class="flex-1 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl p-3 border border-gray-200 dark:border-gray-700 flex flex-col gap-3 min-w-[250px]"
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-gray-300"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-gray-600 dark:text-gray-300"
                    >
                        🐢 Barang Mati
                        <span
                            class="bg-gray-300 text-gray-800 px-1.5 rounded-full text-[10px]"
                            >{{ grouped.dead.length }}</span
                        >
                    </h3>
                </div>
                <div
                    class="flex flex-col gap-2 overflow-y-auto max-h-[600px] pr-1 custom-scrollbar"
                >
                    <ProductKanbanCard
                        v-for="p in grouped.dead"
                        :key="p.id"
                        :data="p"
                        type="dead"
                        @click="openDetail(p.id)"
                    />
                    <div
                        v-if="grouped.dead.length === 0"
                        class="py-8 text-xs italic text-center text-gray-400"
                    >
                        Gudang sehat!
                    </div>
                </div>
            </div>

            <div
                class="flex-1 bg-green-50/50 dark:bg-green-900/10 rounded-xl p-3 border border-green-100 dark:border-green-800 flex flex-col gap-3 min-w-[250px]"
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-green-200"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-green-700"
                    >
                        ✅ Stok Aman
                        <span
                            class="bg-green-200 text-green-800 px-1.5 rounded-full text-[10px]"
                            >{{ grouped.safe.length }}</span
                        >
                    </h3>
                </div>
                <div
                    class="flex flex-col gap-2 overflow-y-auto max-h-[600px] pr-1 custom-scrollbar"
                >
                    <ProductKanbanCard
                        v-for="p in grouped.safe"
                        :key="p.id"
                        :data="p"
                        type="safe"
                        @click="openDetail(p.id)"
                    />
                </div>
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
    background: #d1d5db;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
