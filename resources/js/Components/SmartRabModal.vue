<script setup>
import { computed, ref, watch } from 'vue';
import { useSmartRAB } from '@/Composable/useSmartRAB';

const { isRabModalOpen, rabModalData, closeRabModal, saveToRab } = useSmartRAB();

const quantity = ref(1);

// Initialize quantity when modal opens
watch(isRabModalOpen, (newVal) => {
    if (newVal && rabModalData.value) {
        quantity.value = rabModalData.value.default_qty || 1;
    }
});

const isAlreadyInRab = computed(() => {
    return rabModalData.value?.existing_qty > 0;
});

const existingQty = computed(() => {
    return rabModalData.value?.existing_qty || 0;
});

const productInfo = computed(() => {
    if (!rabModalData.value?.product) return null;
    const p = rabModalData.value.product;
    const isFromPO = !!p.product_snapshot;
    
    return {
        name: isFromPO ? p.product_snapshot.name : p.name,
        code: isFromPO ? p.product_snapshot.code : p.code,
        price: isFromPO ? p.purchase_price : (p.purchase_price || 0),
        image: isFromPO ? p.product_snapshot.image_url : (p.image_url || p.image_path),
        unit: isFromPO ? p.product_snapshot.unit : (p.unit?.name || p.unit || "-"),
        brand: isFromPO ? p.product_snapshot.brand : (p.brand?.name || p.brand || "-"),
    };
});

// Calculate final quantity to be in the cart
const finalQuantity = computed(() => {
    if (isAlreadyInRab.value) {
         return existingQty.value + quantity.value;
    }
    return quantity.value;
});


const increment = () => {
    quantity.value++;
};

const decrement = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const handleSave = () => {
    if (quantity.value < 1) return;
    saveToRab(
        rabModalData.value.product, 
        finalQuantity.value, // Pass the total computed quantity
        rabModalData.value.supplier_id,
        existingQty.value 
    );
};

const rp = (n) => new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
}).format(n || 0);

</script>

<template>
    <div v-if="isRabModalOpen && productInfo" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
        <!-- Backdrop -->
        <div 
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            @click="closeRabModal"
        ></div>

        <!-- Modal Panel -->
        <div class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden transform transition-all animate-slide-up sm:animate-fade-in">
            <!-- Header Image Profile -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex gap-4">
                <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-700 flex-shrink-0 overflow-hidden border border-gray-200 dark:border-gray-600">
                    <img v-if="productInfo.image" :src="productInfo.image" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 text-gray-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0 flex flex-col justify-center">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight line-clamp-2">
                        {{ productInfo.name }}
                    </h3>
                    <p class="text-[11px] text-gray-500 mt-1 font-medium">
                        {{ productInfo.code }} <span class="mx-1 text-gray-300">|</span> {{ productInfo.brand }}
                    </p>
                    <div class="text-xs font-black text-lime-600 dark:text-lime-400 mt-1">
                        {{ rp(productInfo.price) }} <span class="text-[10px] text-gray-400 font-medium">/ {{ productInfo.unit }}</span>
                    </div>
                </div>
            </div>

            <!-- Body Area -->
            <div class="p-5 space-y-4">
                
                <!-- Info Alert if Already in RAB -->
                <div v-if="isAlreadyInRab" class="bg-blue-50 border border-blue-100 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300 p-3 rounded-xl flex gap-3 items-start">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="text-[11px] leading-relaxed">
                        Produk ini sudah ada di keranjang RAB sementara dengan <strong class="font-bold underline">Qty: {{ existingQty }}</strong>. Jika Anda tambahkan, kuantitas akan dijumlahkan.
                    </div>
                </div>

                <!-- Input Qty Control -->
                <div>
                     <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                        Tambahkan Qty (Kuantitas)
                    </label>
                    <div class="flex items-center">
                        <button @click="decrement" type="button" class="w-12 h-12 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-l-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition border border-r-0 border-gray-200 dark:border-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                        </button>
                        <input 
                            v-model.number="quantity"
                            type="number"
                            min="1"
                            class="w-full h-12 text-center text-lg font-black bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-600 dark:text-white focus:ring-0 appearance-none m-0"
                            style="-moz-appearance: textfield;"
                        />
                        <button @click="increment" type="button" class="w-12 h-12 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-r-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition border border-l-0 border-gray-200 dark:border-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                </div>
                 
                 <!-- Subtotal Preview -->
                 <div class="flex justify-between items-center py-2 px-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                     <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Subtotal Penambahan</span>
                     <span class="text-sm font-black text-gray-900 dark:text-white">{{ rp(productInfo.price * quantity) }}</span>
                 </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex gap-3 bg-gray-50 dark:bg-gray-800/80">
                <button 
                    @click="closeRabModal"
                    class="flex-1 py-2.5 px-4 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition"
                >
                    Batal
                </button>
                <button 
                    @click="handleSave"
                    class="flex-1 py-2.5 px-4 text-sm font-bold text-white bg-lime-500 rounded-xl hover:bg-lime-600 active:scale-95 transition shadow-lg shadow-lime-500/30 flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Simpan RAB
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Remove number input arrows */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
.animate-slide-up {
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes slideUp {
    from { transform: translateY(20px) scale(0.95); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@media (max-width: 639px) { /* sm breakpoint */
    .animate-slide-up {
        animation: slideUpMobile 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes slideUpMobile {
        from { transform: translateY(100%); opacity: 0.5; }
        to { transform: translateY(0); opacity: 1; }
    }
}
</style>
