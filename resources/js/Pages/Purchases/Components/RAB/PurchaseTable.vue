<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    items: {
        type: Array, // items from create.vue (cartItems)
        default: () => [],
    },
    isDraft: Boolean,
});

const emit = defineEmits(["remove", "edit", "remove-multiple"]);

const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};

// SORTING: Name ASC
const sortedItems = computed(() => {
    return [...props.items].sort((a, b) => {
        const nameA = a.name?.toLowerCase() || "";
        const nameB = b.name?.toLowerCase() || "";
        if (nameA < nameB) return -1;
        if (nameA > nameB) return 1;
        return 0;
    });
});

const totalAmount = computed(() => {
    return props.items.reduce((sum, item) => sum + (item.purchase_price * item.quantity), 0);
});

// BULK SELECTION
const selectedItems = ref([]); // Stores IDs of selected items
const allSelected = computed({
    get() {
        return props.items.length > 0 && selectedItems.value.length === props.items.length;
    },
    set(value) {
        if (value) {
            selectedItems.value = props.items.map(i => i.product_id || i.id);
        } else {
            selectedItems.value = [];
        }
    }
});

const showConfirmModal = ref(false);

const toggleSelect = (id) => {
    const index = selectedItems.value.indexOf(id);
    if (index === -1) selectedItems.value.push(id);
    else selectedItems.value.splice(index, 1);
};

const handleBulkDelete = () => {
    showConfirmModal.value = true;
};

const confirmDelete = () => {
    emit('remove-multiple', selectedItems.value);
    selectedItems.value = [];
    showConfirmModal.value = false;
};

// Clear selection if items change (e.g. deleted externally) based on IDs preserving valid ones
watch(() => props.items, (newItems) => {
    const activeIds = newItems.map(i => i.product_id || i.id);
    selectedItems.value = selectedItems.value.filter(id => activeIds.includes(id));
}, { deep: true });

</script>

<template>
    <div class="space-y-4">
        <!-- HEADER / SUMMARY -->
        <div class="flex flex-col sm:flex-row justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm sticky top-0 z-20">
            <!-- Left: Select All / Count -->
             <div class="flex items-center gap-4 w-full sm:w-auto">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" v-model="allSelected" class="w-5 h-5 rounded border-gray-300 text-lime-600 focus:ring-lime-500 transition">
                    <span class="font-bold text-gray-700 dark:text-gray-200">
                        {{ selectedItems.length > 0 ? `${selectedItems.length} Terpilih` : 'Pilih Semua' }}
                    </span>
                </label>
                
                <button 
                    v-if="selectedItems.length > 0"
                    @click="handleBulkDelete"
                    class="ml-auto sm:ml-0 px-3 py-1.5 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition flex items-center gap-1 shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>
             </div>

            <!-- Right: Total -->
            <div class="text-right mt-2 sm:mt-0 w-full sm:w-auto border-t sm:border-t-0 pt-2 sm:pt-0 flex justify-between sm:block">
                <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Est.</span>
                <div class="text-xl font-black text-lime-600 dark:text-lime-400">{{ formatRupiah(totalAmount) }}</div>
            </div>
        </div>

        <!-- DESKTOP TABLE -->
        <div class="hidden md:block overflow-hidden border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-10">
                            <!-- Checkbox Column -->
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[40%]">Produk</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga @</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-if="sortedItems.length === 0">
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada item dipilih.</td>
                    </tr>
                    <tr 
                        v-for="item in sortedItems" 
                        :key="item.product_id || item.id" 
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer"
                        :class="selectedItems.includes(item.product_id || item.id) ? 'bg-lime-50/50 dark:bg-lime-900/10' : ''"
                        @click.stop="toggleSelect(item.product_id || item.id)"
                    >
                        <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                            <input 
                                type="checkbox" 
                                :checked="selectedItems.includes(item.product_id || item.id)"
                                @change="toggleSelect(item.product_id || item.id)"
                                class="w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500"
                            >
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded bg-gray-100 border border-gray-200 overflow-hidden relative">
                                    <img v-if="item.image_url || item.image_path" :src="item.image_url || item.image_path" class="h-10 w-10 object-contain p-0.5 mix-blend-multiply" alt="">
                                    <div v-else class="absolute inset-0 flex items-center justify-center text-gray-300">
                                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 max-w-[200px]" :title="item.name">{{ item.name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ item.code }}</div>
                                    <div class="text-[10px] text-gray-400">{{ item.brand }} • {{ item.category }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold" :class="(item.current_stock || 0) <= (item.min_stock || 0) ? 'text-red-600' : 'text-gray-900 dark:text-gray-300'">
                                {{ item.current_stock || item.stock || 0 }}
                            </div>
                            <div class="text-xs text-gray-400 flex items-center gap-1">
                                <span>Min: {{ item.min_stock || 0 }}</span>
                                <span v-if="(item.current_stock || 0) <= (item.min_stock || 0)" class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Stok Kritis"></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                             <button @click="$emit('edit', item)" class="px-2.5 py-1 rounded-md bg-white border border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-50 flex items-center gap-2 shadow-sm">
                                {{ item.quantity }} {{ item.unit_name || item.unit }}
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                            {{ formatRupiah(item.purchase_price) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-white">
                            {{ formatRupiah(item.purchase_price * item.quantity) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                            <button @click="$emit('remove', item)" class="text-gray-400 hover:text-red-600 transition p-1 rounded-full hover:bg-red-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARDS (Visible on Mobile) -->
        <div class="md:hidden space-y-3 pb-20">
            <div v-if="sortedItems.length === 0" class="text-center py-10 text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                Belum ada item dipilih.
            </div>

            <div 
                v-for="item in sortedItems" 
                :key="item.product_id || item.id" 
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-3 flex gap-3 relative overflow-hidden transition-all"
                :class="selectedItems.includes(item.product_id || item.id) ? 'ring-2 ring-lime-500 bg-lime-50/20' : ''"
                @click="toggleSelect(item.product_id || item.id)"
            >
                <!-- Checkbox (Absolute Top Left or integrated clearly) -->
                <!-- Let's make the whole card selectable, but with a visual indicator -->
                <div class="absolute top-3 left-3 z-10" v-if="selectedItems.includes(item.product_id || item.id)">
                     <div class="w-5 h-5 bg-lime-500 rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                     </div>
                </div>

                <!-- Image -->
                <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-lg border border-gray-100 flex items-center justify-center relative overflow-hidden">
                     <img v-if="item.image_url || item.image_path" :src="item.image_url || item.image_path" class="w-full h-full object-contain p-1 mix-blend-multiply" alt="">
                     <div v-else class="text-gray-300 opacity-50">
                          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                     </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0 pl-1">
                    <div class="flex justify-between items-start">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-2 leading-tight mb-1" :title="item.name">{{ item.name }}</h4>
                    </div>
                    
                    <div class="text-[10px] text-gray-500 font-mono mb-2 flex items-center gap-1">
                        <span>{{ item.brand }}</span>
                        <span class="text-gray-300">|</span>
                        <span :class="(item.current_stock || 0) <= (item.min_stock || 0) ? 'text-red-600 font-bold' : ''">
                            Stok: {{ item.current_stock || 0 }} (Min: {{ item.min_stock || 0 }})
                        </span>
                    </div>

                    <div class="flex items-end justify-between">
                        <div class="flex flex-col gap-0.5" @click.stop>
                             <div class="text-xs text-gray-500">
                                @ {{ formatRupiah(item.purchase_price) }}
                             </div>
                             <button @click="$emit('edit', item)" class="text-xs font-bold text-lime-600 bg-lime-50 border border-lime-200 px-2 py-0.5 rounded flex items-center gap-1 mt-1 active:scale-95 transition">
                                <span>qty: {{ item.quantity }}</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                             </button>
                        </div>
                        <div class="text-right">
                             <span class="text-[10px] text-gray-400 uppercase">Subtotal</span>
                             <div class="font-bold text-gray-900 dark:text-white">{{ formatRupiah(item.purchase_price * item.quantity) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Custom Delete Confirmation Modal -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showConfirmModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-sm w-full p-6 shadow-xl border border-gray-100 dark:border-gray-700 transform transition-all">
                    <!-- Icon -->
                    <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-center text-gray-900 dark:text-white mb-2">Hapus {{ selectedItems.length }} Item Terpilih?</h3>
                    <p class="text-center text-gray-500 text-sm mb-6">Tindakan ini akan menghapus item dari daftar RAB. Lanjutkan?</p>
                    <div class="flex gap-3">
                        <button @click="showConfirmModal = false" class="flex-1 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition">Batal</button>
                        <button @click="confirmDelete" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition">Hapus</button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
