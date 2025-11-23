<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import ImageModal from "@/Components/ImageModal.vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import { throttle } from "lodash";
import Search from "./partials/search.vue";

// --- PROPS DARI BACKEND ---
const props = defineProps({
    purchase: Object,
    invoice: Object,
    unlinkedItems: Object, // Produk PO yang belum punya invoice ID
    linkedItems: Object, // Produk PO yang tertaut ke invoice ini
    // Note: Anda perlu menambahkan prop products: Array, di Controller untuk search
    products: { type: Array, default: () => [] },
});

// --- STATE LINKAGE & MODE ---
const { isActionLoading } = useActionLoading();
const toast = useToast();

const isProcessing = ref(false);

// Mode Page (Edit jika 'checking', Detail jika lainnya)
const pageMode = computed(() =>
    props.purchase.status === "checking" ? "edit" : "detail"
);

// [KUNCI]: State untuk Qty dan Harga yang boleh diedit user
// Kita gunakan salinan linkedItems untuk menampung perubahan Qty/Harga
const editableLinkedItems = ref([]);

// State untuk Link/Unlink
const selectedLinkItemIds = ref([]);
const selectedUnlinkItemIds = ref([]);

// State untuk Barang Pengganti/Baru
const showNewItemForm = ref(false); // Kontrol visibility form input manual
const searchKeyword = ref("");
const searchResults = ref([]);
const isSearching = ref(false);
const newProductSelection = ref(null); // Produk master yang dipilih untuk substitusi

// --- FORMS UNTUK SUBMISSION ---
const linkForm = useForm({ item_ids: selectedLinkItemIds });
const unlinkForm = useForm({ item_ids: selectedUnlinkItemIds });
const correctionForm = useForm({ items: editableLinkedItems }); // Form untuk Simpan Koreksi Qty/Harga

// --- INIT LOGIC ---
onMounted(() => {
    // Buat salinan dalam (deep copy) dari linked items saat load
    if (pageMode.value === "edit") {
        editableLinkedItems.value = JSON.parse(
            JSON.stringify(props.linkedItems)
        );
    }
});

// --- COMPUTED DATA & HELPERS ---
const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
const formatTanggal = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
          })
        : "-";
const goBackToChecking = () =>
    router.get(route("purchases.checking", props.purchase.id));

// Perhitungan: Harga per unit yang akan di-overwrite (Display di Card Kanan)
const calculatedPricePerUnit = computed(() => {
    const totalQty = props.linkedItems.reduce(
        (sum, item) => sum + item.quantity,
        0
    );
    if (totalQty === 0) return 0;
    return props.invoice.total_amount / totalQty;
});

// Perhitungan: Total Qty di tabel Koreksi (Editable)
const totalQtyInCorrectionTable = computed(() =>
    editableLinkedItems.value.reduce((sum, item) => sum + item.quantity, 0)
);

// Gambar Nota
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedInvoiceCode = ref(null);
const openImageModal = (path, name) => {
    selectedImageUrl.value = path;
    selectedInvoiceCode.value = name;
    showImageModal.value = true;
};

// --- FUNGSI UTAMA ---

// 1. [LINK ACTION] Menautkan item baru (sudah ada di props.unlinkedItems)
const submitLinkage = () => {
    if (selectedLinkItemIds.value.length === 0) {
        toast.error("Pilih minimal satu item untuk ditautkan.");
        return;
    }
    isActionLoading.value = true;
    isProcessing.value = true;
    console.log(selectedLinkItemIds.value);
    linkForm.post(
        route("purchases.linkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `${selectedLinkItemIds.value.length} item berhasil ditautkan. Harga otomatis diperbarui.`
                );
                router.reload({
                    only: ["unlinkedItems", "linkedItems", "invoice"],
                }); // Reload kedua list
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = true;
                selectedLinkItemIds.value = [];
            },
        }
    );
};

// 2. [UNLINK ACTION] Melepaskan tautan item (sudah ada di linkedItems)
const submitUnlinkage = () => {
    if (selectedUnlinkItemIds.value.length === 0) {
        toast.error("Pilih minimal satu item untuk dilepaskan.");
        return;
    }

    isProcessing.value = true;
    // Gunakan method PUT untuk aksi UPDATE (mengubah link menjadi NULL)
    unlinkForm.put(
        route("purchases.unlinkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.warning(
                    `${selectedUnlinkItemIds.value.length} item dilepaskan. Harga item lain tidak berubah.`
                );
                router.reload({
                    only: ["unlinkedItems", "linkedItems", "invoice"],
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                selectedUnlinkItemIds.value = [];
            },
        }
    );
};

// 3. [BARU] SIMPAN KOREKSI QTY/HARGA (Update Qty/Price di DB)
const saveCorrections = () => {
    isActionLoading.value = true;
    isProcessing.value = true;

    // Perlu validasi FE: Qty tidak boleh < 0
    const invalidQty = editableLinkedItems.value.some(
        (item) => item.quantity < 0
    );
    if (invalidQty) {
        toast.error("Kuantitas yang diterima tidak boleh kurang dari nol.");
        isProcessing.value = false;
        isActionLoading.value = false;
        return;
    }

    correctionForm.items = editableLinkedItems.value;

    // [ENDPOINT BARU DIBUTUHKAN]: purchases.updateLinkedItemDetails (PUT)
    correctionForm.put(
        route("purchases.updateLinkedItemDetails", props.purchase.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Koreksi kuantitas dan harga berhasil disimpan!");
                // Reload hanya items yang tertaut
                router.reload({ only: ["linkedItems"] });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 4. [BARU] Logika Search & Penambahan Barang Pengganti
const handleSearchNewItem = throttle(() => {
    if (searchKeyword.value.length < 2) {
        searchResults.value = [];
        return;
    }
    isSearching.value = true;
    console.log(products);
    // Filter props.products (katalog master) berdasarkan supplier yang sama
    searchResults.value = props.products
        .filter(
            (p) =>
                p.supplier_id == props.purchase.supplier_id &&
                (p.name
                    .toLowerCase()
                    .includes(searchKeyword.value.toLowerCase()) ||
                    p.code
                        .toLowerCase()
                        .includes(searchKeyword.value.toLowerCase()))
        )
        .slice(0, 5); // Tampilkan 5 hasil teratas
    isSearching.value = false;
}, 300);

watch(searchKeyword, handleSearchNewItem);

// Aksi: Menambahkan produk pengganti ke daftar editableLinkedItems
const addNewSubstituteItem = (product) => {
    // Cek apakah item sudah ada di daftar
    const exists = editableLinkedItems.value.some(
        (item) => item.product_id === product.id
    );
    if (exists) {
        toast.warning(
            "Produk ini sudah ada di daftar. Gunakan input Qty untuk menambah."
        );
        return;
    }

    // Tambahkan item baru ke daftar edit
    editableLinkedItems.value.push({
        id: "new_" + Date.now(), // ID sementara untuk FE
        purchase_invoice_id: props.invoice.id,
        product_id: product.id,
        // Qty/Harga awal 0 atau harga master
        quantity: 1,
        purchase_price: product.purchase_price,
        product: product, // Snapshot data
    });

    // Bersihkan UI
    searchKeyword.value = "";
    searchResults.value = [];
    showNewItemForm.value = false;
};
</script>

<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedInvoiceCode"
        @close="showImageModal = false"
    />
    <Head :title="`Tautkan Item ke Nota ${invoice.invoice_number}`" />
    <AuthenticatedLayout
        :headerTitle="`Penautan Item: ${invoice.invoice_number}`"
    >
        <div class="flex items-center justify-between mb-4">
            <SecondaryButton @click="goBackToChecking">
                ← Kembali ke Validasi Utama
            </SecondaryButton>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h3
                        class="pb-2 mb-4 text-xl font-bold border-b dark:text-gray-200"
                    >
                        Koreksi Qty Diterima & Harga Final
                    </h3>
                    <p
                        v-if="linkedItems.length === 0"
                        class="py-8 text-center text-gray-500"
                    >
                        Belum ada item tertaut ke nota ini. Tautkan item dari
                        daftar kanan.
                    </p>

                    <form @submit.prevent="saveCorrections">
                        <div
                            v-if="linkedItems.length > 0"
                            class="overflow-x-auto max-h-[55vh] border rounded dark:border-gray-700 mb-4"
                        >
                            <table class="min-w-full text-sm">
                                <thead
                                    class="sticky top-0 bg-gray-50 dark:bg-gray-700"
                                >
                                    <tr>
                                        <th class="px-4 py-2 text-left">
                                            Produk
                                        </th>
                                        <th class="w-20 px-2 py-2 text-center">
                                            Qty Datang
                                        </th>
                                        <th class="w-24 px-2 py-2 text-right">
                                            Harga Final
                                        </th>
                                        <th class="w-16 px-2 py-2 text-center">
                                            Unlink
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in editableLinkedItems"
                                        :key="item.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <td class="px-4 py-2">
                                            {{ item.product.name }}
                                        </td>

                                        <td class="px-2 py-2 text-center">
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                :disabled="pageMode !== 'edit'"
                                                class="w-full p-1 text-xs text-center border rounded"
                                                min="0"
                                            />
                                        </td>

                                        <td class="px-2 py-2 text-right">
                                            <input
                                                v-model.number="
                                                    item.purchase_price
                                                "
                                                type="number"
                                                :disabled="pageMode !== 'edit'"
                                                class="w-full p-1 text-xs text-right border rounded"
                                                min="0"
                                            />
                                        </td>

                                        <td class="px-2 py-2 text-center">
                                            <input
                                                v-if="pageMode === 'edit'"
                                                type="checkbox"
                                                :value="item.id"
                                                v-model="selectedUnlinkItemIds"
                                                class="text-red-600 rounded"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="pageMode === 'edit'"
                            class="flex items-center justify-between pt-4"
                        >
                            <PrimaryButton
                                type="submit"
                                :disabled="isProcessing"
                                title="Simpan perubahan Qty dan Harga"
                            >
                                Simpan Koreksi Harga/Qty
                            </PrimaryButton>

                            <button
                                @click.prevent="submitUnlinkage"
                                :disabled="
                                    selectedUnlinkItemIds.length === 0 ||
                                    isProcessing
                                "
                                class="px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-red-500 rounded-md hover:bg-red-600"
                            >
                                Lepaskan {{ selectedUnlinkItemIds.length }} Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-1">
                <div
                    class="p-4 rounded-lg shadow bg-yellow-50 dark:bg-yellow-900/30"
                >
                    <h3
                        class="mb-2 text-lg font-bold text-yellow-800 dark:text-yellow-200"
                    >
                        Detail Nota Target
                    </h3>
                    <div class="text-sm dark:text-gray-300">
                        <p class="flex justify-between">
                            <strong>Total Nominal Nota:</strong>
                            <span class="text-xl font-extrabold text-red-600">{{
                                formatRupiah(invoice.total_amount)
                            }}</span>
                        </p>
                        <p class="flex justify-between">
                            <strong>Harga Unit Baru (Perhitungan):</strong>
                            <span class="font-bold text-green-700">{{
                                formatRupiah(calculatedPricePerUnit)
                            }}</span>
                        </p>
                        <p>
                            Jatuh Tempo: {{ formatTanggal(invoice.due_date) }}
                        </p>
                    </div>

                    <div v-if="invoice.invoice_image" class="mt-3">
                        <p class="mb-1 text-xs font-semibold">Bukti Fisik:</p>
                        <img
                            :src="`/storage/${invoice.invoice_image}`"
                            alt="Bukti Nota"
                            class="object-cover w-full h-32 rounded-md shadow cursor-pointer"
                            @click="
                                openImageModal(
                                    invoice.invoice_image,
                                    invoice.invoice_number
                                )
                            "
                        />
                    </div>
                </div>

                <div
                    v-if="pageMode === 'edit'"
                    class="p-4 rounded-lg shadow bg-lime-50 dark:bg-lime-900/30"
                >
                    <h4 class="mb-3 font-bold text-lime-800 dark:text-lime-300">
                        Tambah Item Baru / Pengganti
                    </h4>
                    <Search
                        :isSearching="isSearching"
                        v-model="searchKeyword"
                        :results="searchResults"
                        @select="addNewSubstituteItem"
                        placeholder="Cari produk ..."
                    />
                </div>

                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h4 class="mb-3 font-bold dark:text-gray-200">
                        Item PO Belum Tertaut (Kandidat Link)
                    </h4>
                    <form @submit.prevent="submitLinkage">
                        <p
                            v-if="unlinkedItems.length === 0"
                            class="py-4 text-center text-gray-500"
                        >
                            Tidak ada item PO yang tersisa.
                        </p>
                        <div
                            v-else
                            class="overflow-y-auto border rounded max-h-48 dark:border-gray-700"
                        >
                            <ul class="p-2 space-y-1">
                                <li
                                    v-for="item in unlinkedItems"
                                    :key="item.id"
                                    class="flex items-center justify-between"
                                >
                                    <label
                                        class="flex items-center gap-2 text-sm dark:text-gray-300"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="item.id"
                                            v-model="selectedLinkItemIds"
                                            class="rounded text-lime-600"
                                        />
                                        {{ item.quantity }}x
                                        {{ item.product.name }}
                                    </label>
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="unlinkedItems.length > 0"
                            class="flex justify-end mt-4"
                        >
                            <PrimaryButton
                                type="submit"
                                :disabled="
                                    selectedLinkItemIds.length === 0 ||
                                    isProcessing
                                "
                            >
                                Tautkan Item
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
