<script setup>
const props = defineProps({
    linkedItems: Object,
    openEditModal: Object,
});
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n || 0);
</script>
<template>
    <div>
        <div
            v-if="linkedItems.length === 0"
            class="py-10 text-sm text-center text-gray-400"
        >
            <p>Belum ada item yang ditautkan.</p>
        </div>

        <div
            v-for="item in linkedItems"
            :key="item.id"
            @click="openEditModal(item)"
            class="bg-white dark:bg-gray-900 mb-1 p-3 rounded-xl border border-lime-200 dark:border-lime-900/50 relative overflow-hidden active:scale-[0.98] transition cursor-pointer shadow-sm"
        >
            <div
                class="absolute top-0 bottom-0 left-0 w-1"
                :class="
                    item.product_snapshot.quantity > 0
                        ? 'bg-lime-500'
                        : 'bg-purple-500'
                "
            ></div>
            <div class="flex items-start justify-between pl-2">
                <div>
                    <span
                        :class="[
                            'text-[9px] px-1.5 rounded font-bold border',
                            item.product_snapshot.quantity > 0
                                ? 'bg-lime-100 text-lime-600 border-lime-200 dark:bg-lime-900/20 dark:text-lime-400'
                                : 'bg-purple-100 text-purple-600 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400',
                        ]"
                        >{{
                            item.product_snapshot.quantity > 0
                                ? "Sesuai Purchase Order"
                                : "Produk Tambahan / Baru"
                        }}</span
                    >
                    <h4
                        class="mb-1 text-sm font-bold text-gray-800 dark:text-white"
                    >
                        {{ item.product?.name }}
                    </h4>
                    <div class="flex gap-3 text-xs">
                        <span class="text-gray-500"
                            >Qty:
                            <b class="text-gray-800 dark:text-gray-200">{{
                                item.quantity
                            }}</b></span
                        >
                        <span class="text-gray-500"
                            >@
                            <b class="text-gray-800 dark:text-gray-200">{{
                                rp(item.purchase_price)
                            }}</b></span
                        >
                    </div>
                    <span class="text-sm text-gray-500"
                        >Total :
                        <b class="font-mono text-gray-800 dark:text-gray-200">{{
                            rp(item.purchase_price)
                        }}</b></span
                    >
                </div>
                <!-- <div class="text-gray-300">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                        />
                    </svg>
                </div> -->
            </div>
        </div>
    </div>
</template>
