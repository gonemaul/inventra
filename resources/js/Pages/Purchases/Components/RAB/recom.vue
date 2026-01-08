<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    show: Boolean,
    supplierId: [Number, String],
});

const emit = defineEmits(["close", "add-items"]);

// --- STATE ---
const recommendations = ref([]);
const loading = ref(false);
const selectedItems = ref([]); // Local state untuk checkbox

// --- HELPER ---
const formatRupiah = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);

// --- FETCH DATA ---
async function fetchRecommendations() {
    if (!props.supplierId) return;

    loading.value = true;
    try {
        const res = await axios.get(
            route("purchases.recommendations", props.supplierId)
        );
        recommendations.value = res.data;

        // Auto-Select Logic:
        // Default centang item yang KRITIS (stok sedikit) agar User terbantu
        selectedItems.value = res.data
            .filter((i) => i.is_critical)
            .map((i) => i.product_id);
    } catch (e) {
        console.error("Gagal fetch rekomendasi:", e);
        recommendations.value = [];
        selectedItems.value = [];
    } finally {
        loading.value = false;
    }
}

// --- WATCHERS ---
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            fetchRecommendations();
        } else {
            // Reset data saat tutup (diberi delay sedikit agar animasi close mulus)
            setTimeout(() => {
                recommendations.value = [];
                selectedItems.value = [];
            }, 300);
        }
    }
);

// --- COMPUTED PROPERTIES ---

// 1. Cek status "Select All"
const allChecked = computed({
    get() {
        return (
            recommendations.value.length > 0 &&
            selectedItems.value.length === recommendations.value.length
        );
    },
    set(value) {
        selectedItems.value = value
            ? recommendations.value.map((i) => i.product_id)
            : [];
    },
});

// 2. Hitung Total Estimasi Biaya (Untuk Footer Sticky)
const totalSelectedCost = computed(() => {
    return recommendations.value
        .filter((item) => selectedItems.value.includes(item.product_id))
        .reduce((sum, item) => sum + item.purchase_price * item.quantity, 0);
});

// --- ACTIONS ---

// Toggle checkbox per item
const toggleSelection = (id) => {
    const index = selectedItems.value.indexOf(id);
    if (index === -1) {
        selectedItems.value.push(id);
    } else {
        selectedItems.value.splice(index, 1);
    }
};

// Kirim data ke Parent Component (Form Pembelian)
function handleAdd() {
    // Filter hanya item yang dipilih
    const itemsToAdd = recommendations.value
        .filter((item) => selectedItems.value.includes(item.product_id))
        .map((item) => ({
            // Mapping sesuai struktur "cart" di halaman pembelian Anda
            product_id: item.product_id,
            code: item.code,
            name: item.name,
            unit: item.unit, // Pastikan backend mengirim object unit/size
            size: item.size,

            // Masukkan data transaksi
            purchase_price: item.purchase_price,
            quantity: item.quantity, // Qty rekomendasi sistem
            subtotal: item.purchase_price * item.quantity,

            // Info tambahan (opsional)
            current_stock: item.current_stock,
        }));

    if (itemsToAdd.length === 0) return;

    emit("add-items", itemsToAdd);
    emit("close");
}
</script>

<template>
    <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="💡 Rekomendasi Restock Cerdas"
    >
        <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900">
            <div
                class="px-6 py-4 bg-white border-b shadow-sm dark:bg-gray-800 dark:border-gray-700 shrink-0"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Sistem mendeteksi
                            <strong class="text-gray-800 dark:text-gray-200"
                                >{{ recommendations.length }} barang</strong
                            >
                            di bawah stok minimum atau diprediksi habis.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <label
                            class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold transition-colors border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                            :class="
                                allChecked
                                    ? 'bg-lime-50 border-lime-200 text-lime-700'
                                    : 'bg-white border-gray-200 text-gray-600 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300'
                            "
                        >
                            <input
                                type="checkbox"
                                v-model="allChecked"
                                class="w-4 h-4 border-gray-300 rounded text-lime-600 focus:ring-lime-500"
                            />
                            Pilih Semua ({{ recommendations.length }})
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex-1 p-4 overflow-y-auto custom-scrollbar">
                <div
                    v-if="loading"
                    class="flex flex-col items-center justify-center py-20 space-y-4"
                >
                    <div class="relative w-12 h-12">
                        <div
                            class="absolute w-full h-full border-4 rounded-full border-lime-200 opacity-30"
                        ></div>
                        <div
                            class="absolute w-full h-full border-4 border-l-4 rounded-full border-lime-500 animate-spin border-t-transparent border-r-transparent border-b-transparent"
                        ></div>
                    </div>
                    <span
                        class="text-sm font-medium text-gray-500 animate-pulse"
                        >Menganalisa data stok...</span
                    >
                </div>

                <div
                    v-else-if="recommendations.length === 0"
                    class="flex flex-col items-center justify-center py-20 text-center"
                >
                    <div
                        class="p-4 mb-4 bg-green-100 rounded-full dark:bg-green-900/30"
                    >
                        <svg
                            class="w-10 h-10 text-green-600 dark:text-green-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>
                    <h3
                        class="text-lg font-bold text-gray-800 dark:text-gray-100"
                    >
                        Stok Aman!
                    </h3>
                    <p class="max-w-xs mt-1 text-sm text-gray-500">
                        Tidak ada barang dari supplier ini yang perlu di-restock
                        saat ini.
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        class="flex flex-col overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-900 rounded-xl dark:border-gray-800"
                    >
                        <div
                            v-for="item in recommendations"
                            :key="item.product_id"
                            @click="toggleSelection(item.product_id)"
                            class="relative flex items-center gap-3 p-3 transition-colors border-b border-gray-100 cursor-pointer group last:border-0 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            :class="[
                                // Logic Background saat Selected
                                selectedItems.includes(item.product_id)
                                    ? 'bg-lime-50 dark:bg-lime-900/10'
                                    : 'bg-white dark:bg-gray-900',
                                // Logic Border Strip Kiri (Status Critical)
                                item.is_critical
                                    ? 'border-l-4 border-l-red-500 pl-2' // pl-2 karena border makan tempat
                                    : 'border-l-4 border-l-transparent pl-2',
                            ]"
                        >
                            <div class="shrink-0">
                                <input
                                    type="checkbox"
                                    :value="item.product_id"
                                    v-model="selectedItems"
                                    @click.stop
                                    class="w-5 h-5 border-gray-300 rounded cursor-pointer text-lime-600 focus:ring-lime-500"
                                />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span
                                        v-if="item.brand"
                                        class="px-1.5 py-0.5 text-[9px] font-bold text-gray-500 bg-gray-100 rounded dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        {{ item.brand }}
                                    </span>
                                    <h4
                                        class="text-sm font-bold text-gray-800 truncate dark:text-gray-100"
                                    >
                                        {{ item.name }}
                                    </h4>
                                </div>

                                <div
                                    class="flex items-center gap-2 text-[10px] text-gray-400 mb-2"
                                >
                                    <span class="font-mono"
                                        >#{{ item.code }}</span
                                    >
                                    <span>•</span>
                                    <span
                                        >{{ item.size }} / {{ item.unit }}</span
                                    >

                                    <span
                                        v-if="item.is_critical"
                                        class="ml-auto text-red-600 font-bold bg-red-50 px-1.5 rounded-sm"
                                    >
                                        {{ item.reason }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden dark:bg-gray-800"
                                    >
                                        <div
                                            class="h-full rounded-full"
                                            :class="
                                                item.is_critical
                                                    ? 'bg-red-500'
                                                    : 'bg-yellow-400'
                                            "
                                            :style="{
                                                width:
                                                    Math.min(
                                                        (item.current_stock /
                                                            item.min_stock) *
                                                            100,
                                                        100
                                                    ) + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <span
                                        class="text-[9px] text-gray-400 whitespace-nowrap"
                                    >
                                        {{ item.current_stock }} /
                                        {{ item.min_stock }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="text-right pl-2 border-l border-dashed border-gray-200 dark:border-gray-700 min-w-[80px]"
                            >
                                <span
                                    class="text-[9px] text-gray-400 uppercase font-medium"
                                    >Order</span
                                >

                                <div
                                    class="text-lg font-black leading-none text-blue-600 dark:text-blue-400 my-0.5"
                                >
                                    +{{ item.quantity }}
                                </div>

                                <div
                                    class="text-[10px] text-gray-500 dark:text-gray-400 font-medium"
                                >
                                    {{
                                        formatRupiah(
                                            item.purchase_price * item.quantity
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div
                        v-for="item in recommendations"
                        :key="item.product_id"
                        @click="toggleSelection(item.product_id)"
                        class="relative flex flex-col gap-3 p-4 transition-all bg-white border rounded-xl cursor-pointer dark:bg-gray-800 group hover:shadow-md active:scale-[0.99]"
                        :class="[
                            selectedItems.includes(item.product_id)
                                ? 'border-lime-500 ring-1 ring-lime-500 bg-lime-50/10'
                                : 'border-gray-200 dark:border-gray-700 hover:border-lime-300',
                            item.is_critical
                                ? 'border-l-4 border-l-red-500'
                                : 'border-l-4 border-l-transparent',
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <div class="pt-1">
                                <input
                                    type="checkbox"
                                    :value="item.product_id"
                                    v-model="selectedItems"
                                    @click.stop
                                    class="w-5 h-5 border-gray-300 rounded cursor-pointer text-lime-600 focus:ring-lime-500"
                                />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <span
                                        class="text-[10px] font-bold tracking-wider text-gray-400 uppercase truncate"
                                    >
                                        {{ item.brand || "Umum" }} • #{{
                                            item.code
                                        }}
                                    </span>

                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border"
                                        :class="
                                            item.is_critical
                                                ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'
                                                : 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300'
                                        "
                                    >
                                        {{ item.reason || "Stok Menipis" }}
                                    </span>
                                </div>

                                <h4
                                    class="mb-2 text-sm font-bold text-gray-800 dark:text-gray-100 line-clamp-1"
                                >
                                    {{ item.name }}
                                </h4>
                                <span
                                    class="text-[10px] font-bold tracking-wider text-gray-400 uppercase truncate"
                                >
                                    {{ item.size }} / {{ item.unit }}
                                </span>

                                <div
                                    class="flex items-center gap-3 pt-2 border-t border-dashed"
                                >
                                    <div class="flex-1">
                                        <div
                                            class="flex justify-between mb-1 text-[10px]"
                                        >
                                            <span class="text-gray-500"
                                                >Stok:
                                                <b
                                                    :class="
                                                        item.current_stock <=
                                                        item.min_stock
                                                            ? 'text-red-600'
                                                            : 'text-gray-700'
                                                    "
                                                    >{{ item.current_stock }}</b
                                                ></span
                                            >
                                            <span class="text-gray-400"
                                                >Min: {{ item.min_stock }}</span
                                            >
                                        </div>
                                        <div
                                            class="w-full h-1.5 bg-gray-100 rounded-full dark:bg-gray-700 overflow-hidden"
                                        >
                                            <div
                                                class="h-full transition-all duration-500 rounded-full"
                                                :style="{
                                                    width:
                                                        Math.min(
                                                            (item.current_stock /
                                                                item.min_stock) *
                                                                100,
                                                            100
                                                        ) + '%',
                                                }"
                                                :class="
                                                    item.is_critical
                                                        ? 'bg-red-500'
                                                        : 'bg-yellow-400'
                                                "
                                            ></div>
                                        </div>
                                    </div>

                                    <div
                                        class="w-px h-8 bg-gray-200 dark:bg-gray-700"
                                    ></div>

                                    <div
                                        class="flex flex-col items-end min-w-[80px]"
                                    >
                                        <span class="text-[10px] text-gray-400"
                                            >Saran Order</span
                                        >
                                        <span
                                            class="text-lg font-black text-blue-600 dark:text-blue-400"
                                        >
                                            +{{ item.quantity }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between pt-2 mt-1 border-t border-gray-100 border-dashed dark:border-gray-700"
                        >
                            <span class="text-[10px] text-gray-400"
                                >Estimasi Biaya:</span
                            >
                            <span
                                class="text-xs font-bold text-gray-700 dark:text-gray-300"
                            >
                                {{
                                    formatRupiah(
                                        item.purchase_price * item.quantity
                                    )
                                }}
                            </span>
                        </div>
                    </div> -->
                </div>
            </div>

            <div
                class="px-6 py-4 bg-white border-t shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] dark:bg-gray-800 dark:border-gray-700 z-10 shrink-0"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500"
                            >Total Estimasi ({{
                                selectedItems.length
                            }}
                            item)</span
                        >
                        <span
                            class="text-lg font-black text-lime-600 dark:text-lime-400"
                        >
                            {{ formatRupiah(totalSelectedCost) }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Batal
                    </button>

                    <button
                        @click="handleAdd"
                        :disabled="selectedItems.length === 0"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold text-white transition rounded-xl shadow-lg shadow-lime-500/30"
                        :class="
                            selectedItems.length > 0
                                ? 'bg-lime-600 hover:bg-lime-700 active:scale-[0.98]'
                                : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700 text-gray-400 shadow-none'
                        "
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
                                d="M12 4v16m8-8H4"
                            ></path>
                        </svg>
                        <span>Tambahkan ke Keranjang</span>
                    </button>
                </div>
            </div>
        </div>
    </BottomSheet>
</template>
