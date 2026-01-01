<script setup>
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import { ref, watch, computed, reactive } from "vue";
import BottomSheet from "@/Components/BottomSheet.vue";
import InputRupiah from "@/Components/InputRupiah.vue";

const { isActionLoading } = useActionLoading();
const toast = useToast();
const props = defineProps({
    show: Boolean,
    purchase: Object, // Data purchase untuk kalkulasi preview
});

const emit = defineEmits(["close"]);

const form = useForm({
    shipping_cost: 0,
    other_costs: 0,
    notes: "",
});

const isProcessing = ref(false);

// Reset form saat modal dibuka
watch(
    () => props.show,
    (val) => {
        if (val) {
            form.shipping_cost = 0;
            form.other_costs = 0;
            form.notes = "";
        }
    }
);

const submitFinalize = () => {
    isProcessing.value = true;
    isActionLoading.value = true;
    form.put(route("purchases.finalize", props.purchase.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            isProcessing.value = false;
        },
        onError: () => {
            isActionLoading.value = false;
            isProcessing.value = false;
            toast.error(
                errors.message || "Terjadi kesalahan saat menghapus data."
            );
        },
        onFinish: () => {
            isActionLoading.value = false;
            isProcessing.value = false;
        },
    });
};

const reviewItems = computed(() => {
    return props.purchase.items.map((item) => {
        const snapshot = item.product_snapshot || {};
        const snapshotQty = snapshot.quantity || 0;
        const snapshotPrice = snapshot.purchase_price || 0;

        // KATEGORI 1: ITEM TIDAK TERTAUT (UNLINKED) -> Dianggap Kosong/Batal
        if (!item.purchase_invoice_id) {
            return {
                ...item,
                review_status: "cancelled",
                review_label: "Batal / Kosong",
                review_class: "bg-red-50 text-red-700 border-red-200",
                review_desc:
                    "Item ini tidak masuk nota manapun. Stok tidak akan bertambah.",
            };
        }

        // KATEGORI 2: ITEM RETUR (Jika ada flag khusus, misal item_status === 'returned')
        // Asumsi: Anda punya kolom 'item_status' atau 'is_returned'
        if (item.item_status === "returned") {
            return {
                ...item,
                review_status: "returned",
                review_label: "Diretur",
                review_class: "bg-orange-50 text-orange-700 border-orange-200",
                review_desc: item.note
                    ? `Alasan: ${item.note}`
                    : "Dikembalikan ke supplier.",
            };
        }

        // KATEGORI 3: BARANG BARU / PENGGANTI (Tidak ada di Snapshot PO Awal)
        // Logika: Jika tidak ada snapshot, berarti item ini ditambahkan saat proses checking
        if (!item.product_snapshot) {
            return {
                ...item,
                review_status: "new",
                review_label: "Item Baru/Pengganti",
                review_class: "bg-blue-50 text-blue-700 border-blue-200",
                review_desc: "Item tambahan yang tidak ada di PO awal.",
            };
        }

        // KATEGORI 4: SELISIH (Ada beda Harga atau Qty)
        const isQtyDiff = item.quantity !== snapshotQty;
        const isPriceDiff = item.purchase_price !== snapshotPrice;

        if (isQtyDiff || isPriceDiff) {
            let desc = [];
            if (isQtyDiff) desc.push(`Qty: ${snapshotQty} → ${item.quantity}`);
            if (isPriceDiff)
                desc.push(
                    `Harga: ${rp(snapshotPrice)} → ${rp(item.purchase_price)}`
                );

            return {
                ...item,
                review_status: "diff",
                review_label: "Perubahan Data",
                review_class: "bg-yellow-50 text-yellow-700 border-yellow-200",
                review_desc: desc.join(", "),
            };
        }

        // KATEGORI 5: SESUAI (Perfect Match)
        return {
            ...item,
            review_status: "ok",
            review_label: "Sesuai",
            review_class: "bg-gray-50 text-gray-600 border-gray-100", // Warna netral agar tidak mendominasi
            review_desc: "Sesuai pesanan.",
        };
    });
});

// Default: Yang bermasalah (cancelled, new, diff) terbuka. Yang OK tertutup.
const accordionState = reactive({
    cancelled: true,
    new: true,
    diff: true,
    ok: false,
});

const toggleSection = (key) => {
    accordionState[key] = !accordionState[key];
};
const groupedItems = computed(() => {
    // Ambil hasil olahan reviewItems sebelumnya
    const all = reviewItems.value;

    return {
        cancelled: all.filter((i) => i.review_status === "cancelled"),
        new: all.filter((i) => i.review_status === "new"),
        diff: all.filter((i) => i.review_status === "diff"),
        ok: all.filter((i) => i.review_status === "ok"),
    };
});
const getGroupSummary = (group) => {
    return `${group.length} Item`;
};
const financialSummary = computed(() => {
    const totalPO = props.purchase.grand_total;

    // Total Item yang Valid (Sudah ada di invoice)
    const totalItemsReal = props.purchase.invoices.reduce(
        (acc, inv) => acc + inv.total_amount,
        0
    );

    // Grand Total Realisasi (Item + Ongkir + Lainnya)
    const grandTotalReal =
        totalItemsReal + form.shipping_cost + form.other_costs;

    return {
        po: totalPO,
        realItems: totalItemsReal, // Subtotal Barang
        realTotal: grandTotalReal, // Total Akhir
        diff: grandTotalReal - totalPO,
        isOver: grandTotalReal > totalPO,
    };
});
const rp = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
</script>

<template>
    <!-- <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="Selesaikan & Masukkan Stok"
    >
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Selesaikan & Masukkan Stok
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Pastikan semua data valid. Aksi ini akan menambah stok dan tidak
                dapat dibatalkan. Silakan masukkan biaya tambahan jika ada (akan
                dibebankan ke HPP produk).
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <InputLabel
                        for="shipping"
                        value="Biaya Ongkos Kirim (Opsional)"
                    />
                    <TextInput
                        id="shipping"
                        v-model="form.shipping_cost"
                        type="number"
                        class="block w-full mt-1"
                        min="0"
                    />
                </div>

                <div>
                    <InputLabel
                        for="other"
                        value="Biaya Lain-lain / Admin (Opsional)"
                    />
                    <TextInput
                        id="other"
                        v-model="form.other_costs"
                        type="number"
                        class="block w-full mt-1"
                        min="0"
                    />
                </div>

                <div>
                    <InputLabel
                        for="notes"
                        value="Catatan Tambahan (Opsional)"
                    />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-lime-500 focus:ring-lime-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300"
                        placeholder="Contoh: Barang diterima lengkap, bonus 2 pcs..."
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <SecondaryButton @click="$emit('close')">
                    Batal
                </SecondaryButton>

                <PrimaryButton
                    class="bg-lime-600 hover:bg-lime-700"
                    :disabled="isProcessing || form.processing"
                    @click="submitFinalize"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Selesaikan Transaksi</span>
                </PrimaryButton>
            </div>
        </div>
    </BottomSheet> -->
    <BottomSheet
        :show="show"
        title="📝 Review & Finalisasi"
        @close="$emit('close')"
    >
        <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900">
            <div class="flex-1 overflow-y-auto">
                <div
                    class="p-4 mb-4 bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span
                            class="text-xs font-bold tracking-wider text-gray-500 uppercase"
                            >Total Realisasi</span
                        >
                        <span
                            class="px-2 py-1 text-xs font-bold rounded"
                            :class="
                                financialSummary.isOver
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-emerald-100 text-emerald-700'
                            "
                        >
                            {{ financialSummary.isOver ? "Boros" : "Hemat" }}
                            {{ rp(Math.abs(financialSummary.diff)) }}
                        </span>
                    </div>
                    <div class="items-baseline gap-1">
                        <h2
                            class="text-2xl font-black text-gray-900 dark:text-white"
                        >
                            {{ rp(financialSummary.realTotal) }}
                        </h2>
                        <span class="text-xs text-gray-400"
                            >vs PO: {{ rp(financialSummary.po) }}</span
                        >
                    </div>
                </div>

                <h3
                    class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-300"
                >
                    📦 Rincian Barang
                </h3>
                <div class="mb-6 space-y-3">
                    <div
                        v-if="groupedItems.cancelled.length > 0"
                        class="overflow-hidden border border-red-200 rounded-xl dark:border-red-900/50"
                    >
                        <button
                            @click="toggleSection('cancelled')"
                            class="flex items-center justify-between w-full p-3 transition bg-red-50 dark:bg-red-900/20 hover:bg-red-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full"
                                >
                                    {{ groupedItems.cancelled.length }}
                                </div>
                                <div
                                    class="text-sm font-bold text-red-700 dark:text-red-400"
                                >
                                    Batal / Kosong
                                </div>
                            </div>
                            <svg
                                class="w-5 h-5 text-red-400 transition-transform duration-200"
                                :class="
                                    accordionState.cancelled ? 'rotate-180' : ''
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="accordionState.cancelled"
                            class="p-3 space-y-2 bg-white dark:bg-gray-800"
                        >
                            <div
                                v-for="item in groupedItems.cancelled"
                                :key="item.id"
                                class="flex items-center justify-between p-2 border border-red-200 border-dashed rounded-lg bg-red-50/50"
                            >
                                <div>
                                    <div
                                        class="text-sm font-bold text-gray-800"
                                    >
                                        {{ item.product?.name }}
                                    </div>
                                    <div
                                        class="text-xs font-medium text-red-500"
                                    >
                                        Stok tidak masuk (0)
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400 line-through"
                                    >Qty:
                                    {{ item.product_snapshot?.quantity }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="groupedItems.new.length > 0"
                        class="overflow-hidden border border-blue-200 rounded-xl dark:border-blue-900/50"
                    >
                        <button
                            @click="toggleSection('new')"
                            class="flex items-center justify-between w-full p-3 transition bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-500 rounded-full"
                                >
                                    {{ groupedItems.new.length }}
                                </div>
                                <div
                                    class="text-sm font-bold text-blue-700 dark:text-blue-400"
                                >
                                    Item Tambahan (Baru)
                                </div>
                            </div>
                            <svg
                                class="w-5 h-5 text-blue-400 transition-transform"
                                :class="accordionState.new ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="accordionState.new"
                            class="p-3 space-y-2 bg-white dark:bg-gray-800"
                        >
                            <div
                                v-for="item in groupedItems.new"
                                :key="item.id"
                                class="flex items-center justify-between p-2 border border-blue-100 rounded-lg"
                            >
                                <div class="text-sm font-bold text-gray-800">
                                    {{ item.product?.name }}
                                </div>
                                <div class="font-bold text-blue-600">
                                    +{{ item.quantity }}
                                    {{ item.product?.unit?.name }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="groupedItems.diff.length > 0"
                        class="overflow-hidden border border-yellow-200 rounded-xl dark:border-yellow-900/50"
                    >
                        <button
                            @click="toggleSection('diff')"
                            class="flex items-center justify-between w-full p-3 transition bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-yellow-500 rounded-full"
                                >
                                    {{ groupedItems.diff.length }}
                                </div>
                                <div
                                    class="text-sm font-bold text-yellow-700 dark:text-yellow-400"
                                >
                                    Perubahan Data
                                </div>
                            </div>
                            <svg
                                class="w-5 h-5 text-yellow-400 transition-transform"
                                :class="accordionState.diff ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="accordionState.diff"
                            class="p-3 space-y-2 bg-white dark:bg-gray-800"
                        >
                            <div
                                v-for="item in groupedItems.diff"
                                :key="item.id"
                                class="p-2 border border-yellow-100 rounded-lg bg-yellow-50/30"
                            >
                                <div
                                    class="mb-1 text-sm font-bold text-gray-800"
                                >
                                    {{ item.product?.name }}
                                </div>
                                <div
                                    class="flex justify-between text-xs text-gray-500"
                                >
                                    <span>{{ item.review_desc }}</span>
                                    <span class="font-bold text-gray-900"
                                        >Final: {{ item.quantity }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="groupedItems.ok.length > 0"
                        class="overflow-hidden border border-gray-200 rounded-xl dark:border-gray-700"
                    >
                        <button
                            @click="toggleSection('ok')"
                            class="flex items-center justify-between w-full p-3 transition bg-gray-50 dark:bg-gray-800 hover:bg-gray-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100"
                                >
                                    <svg
                                        class="w-4 h-4 text-emerald-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                                <div
                                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                >
                                    {{ groupedItems.ok.length }} Item Sesuai
                                </div>
                            </div>
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform"
                                :class="accordionState.ok ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="accordionState.ok"
                            class="p-3 bg-white border-t dark:bg-gray-900 dark:border-gray-700"
                        >
                            <div class="grid grid-cols-1 gap-2">
                                <div
                                    v-for="item in groupedItems.ok"
                                    :key="item.id"
                                    class="flex justify-between py-1 text-xs border-b border-gray-50 last:border-0 dark:border-gray-800"
                                >
                                    <span
                                        class="w-2/3 text-gray-600 truncate"
                                        >{{ item.product?.name }}</span
                                    >
                                    <span
                                        class="font-bold text-gray-900 dark:text-gray-100"
                                        >{{ item.quantity }}
                                        {{ item.product?.unit?.name }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3
                    class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-300"
                >
                    💰 Biaya & Catatan
                </h3>
                <div
                    class="p-4 space-y-4 bg-white border rounded-xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div>
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >Biaya Pengiriman</label
                        >
                        <InputRupiah
                            v-model="form.shipping_cost"
                            placeholder="Rp 0"
                            min="0"
                            class="w-full py-2 pl-10 pr-3 font-bold text-gray-800 border border-gray-200 rounded-lg bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >Biaya Lainnya</label
                        >
                        <InputRupiah
                            v-model="form.other_costs"
                            placeholder="Rp 0"
                            min="0"
                            class="w-full py-2 pl-10 pr-3 font-bold text-gray-800 border border-gray-200 rounded-lg bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >Catatan Finalisasi</label
                        >
                        <textarea
                            v-model="form.note"
                            rows="2"
                            class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-200 rounded-lg bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Contoh: Barang pengganti disetujui oleh Owner..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <div
                class="p-4 bg-white border-t dark:bg-gray-900 dark:border-gray-800"
            >
                <div class="flex justify-between mb-4 text-xs text-gray-500">
                    <span
                        >Subtotal Barang:
                        {{ rp(financialSummary.realItems) }}</span
                    >
                    <span
                        >+ Biaya:
                        {{ rp(form.shipping_cost + form.other_costs) }}</span
                    >
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-3 text-sm font-bold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitFinalize"
                        class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-white shadow-lg bg-lime-600 rounded-xl hover:bg-lime-700 shadow-lime-200 dark:shadow-none"
                    >
                        <span>Selesai</span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </BottomSheet>
</template>
