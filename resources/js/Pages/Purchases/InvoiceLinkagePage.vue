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

// --- FORMS UNTUK SUBMISSION ---
const linkForm = useForm({ product_ids: [], type: 'link' });
const singleLinkForm = useForm({product_ids: [], type: 'create'});
const unlinkForm = useForm({ item_ids: [] });
const correctionForm = useForm({ items: editableLinkedItems }); // Form untuk Simpan Koreksi Qty/Harga

// --- INIT LOGIC ---
onMounted(() => {
    // Buat salinan dalam (deep copy) dari linked items saat load
    // if (pageMode.value === "edit") {
        editableLinkedItems.value = JSON.parse(
            JSON.stringify(props.linkedItems)
        );
    // }
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


const computedTotalNominal = computed(() => {
    // Menghitung total subtotal dari semua item yang dapat diedit
    return editableLinkedItems.value.reduce((sum, item) => {
        // [PENTING]: Hitung subtotal (Qty * Price) untuk setiap baris, lalu jumlahkan
        const subtotal = (item.quantity || 0) * (item.purchase_price || 0);
        return sum + subtotal;
    }, 0);
});
const computedTotalQty = computed(() => {
    // Menghitung total kuantitas dari semua item
    return editableLinkedItems.value.reduce((sum, item) => {
        return sum + (item.quantity || 0);
    }, 0);
});

// Gambar Nota
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedInvoiceCode = ref(null);
const openImageModal = (path, name) => {
    selectedImageUrl.value = path;
    selectedInvoiceCode.value = "Invoice-#" +name;
    showImageModal.value = true;
};

// --- FUNGSI UTAMA ---

// 1. [LINK ACTION] Menautkan item baru (sudah ada di props.unlinkedItems)
const submitLinkage = () => {
    if (selectedLinkItemIds.value.length === 0) {
        toast.error("Pilih minimal satu item untuk ditautkan.");
        return;
    }
    linkForm.product_ids = [];
    linkForm.product_ids = selectedLinkItemIds.value;
    isActionLoading.value = true;
    isProcessing.value = true;
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
                // router.reload({
                //     only: ["unlinkedItems", "linkedItems", "invoice"],
                // });
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onError: (error) => {
                toast.success(`Terdapat kesalahan : ${error}`);
                console.log(error);
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
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
    unlinkForm.item_ids = [];
    unlinkForm.item_ids = selectedUnlinkItemIds.value;
    isActionLoading.value = true;
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
                // router.reload({
                //     only: ["unlinkedItems", "linkedItems", "invoice"],
                // });
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
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
        route("purchases.updateLinkedItemDetails", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`${editableLinkedItems.value.length} Produk berhasil diperbarui!`);
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
    // Filter props.products (katalog master) berdasarkan supplier yang sama
    searchResults.value = props.products
        .filter(
            (p) =>
                p.name
                    .toLowerCase()
                    .includes(searchKeyword.value.toLowerCase()) ||
                p.code.toLowerCase().includes(searchKeyword.value.toLowerCase())
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

    singleLinkForm.product_ids = [];
    singleLinkForm.product_ids = [product.id]
    console.log(singleLinkForm.product_ids)
    isActionLoading.value = true;
    isProcessing.value = true;
    singleLinkForm.post(
        route("purchases.linkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `Produk berhasil ditambahkan kedalam Invoice`
                );
                // router.reload({
                //     only: ["unlinkedItems", "linkedItems", "invoice"],
                // });
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onError: (error) => {
                toast.success(`Terdapat kesalahan : ${error}`);
                console.log(error);
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
                selectedLinkItemIds.value = [];
            },
        }
    );
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
    <Head :title="`Invoice #${invoice.invoice_number}`" />
    <AuthenticatedLayout :headerTitle="`Invoice : #${invoice.invoice_number}`" :showSidebar="false">
        <div class="flex items-center justify-between mb-4">
            <SecondaryButton @click="goBackToChecking">
                ← Kembali ke Validasi Utama
            </SecondaryButton>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="p-6 bg-white border-2 border-gray-100 rounded-lg shadow-lg dark:bg-gray-800">
                    <h3
                        class="pb-2 mb-4 text-xl font-bold border-b dark:text-gray-200"
                    >
                        Koreksi Qty Diterima & Harga Final
                    </h3>
                    <p
                        v-if="linkedItems.length === 0"
                        class="py-8 text-center text-gray-500"
                    >
                        Belum ada produk tertaut ke nota ini. Tautkan item dari
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
                                        <th v-if="pageMode.value === 'edit'" class="w-16 px-2 py-2 text-center">
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
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold text-gray-800 dark:text-white"
                                                >
                                                    {{
                                                        item.product_snapshot
                                                            .name
                                                    }}
                                                </span>
                                                <span
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{
                                                        item.product_snapshot
                                                            .brand
                                                    }}
                                                    |
                                                    {{
                                                        item.product_snapshot
                                                            .code
                                                    }}
                                                    |
                                                    {{
                                                        item.product_snapshot
                                                            .category
                                                    }}
                                                </span>
                                                <span
                                                    class="text-xs text-gray-400"
                                                >
                                                    {{
                                                        item.product_snapshot
                                                            .size
                                                    }}
                                                    -
                                                    {{
                                                        item.product_snapshot
                                                            .unit
                                                    }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="w-1/12 px-2 py-2 text-center">
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                :disabled="pageMode !== 'edit'"
                                                :class="{ 'opacity-50 cursor-not-allowed': pageMode !== 'edit' }"
                                                class="w-full px-2 py-1 text-sm text-center border rounded dark:text-gray-800"
                                                min="0"
                                            />
                                        </td>

                                        <td class="w-1/6 px-2 py-2 text-right">
                                            <span class="flex gap-2">
                                                Rp
                                                <input
                                                    v-model.number="
                                                        item.purchase_price
                                                    "
                                                    type="number"
                                                    :disabled="
                                                        pageMode !== 'edit'
                                                    "
                                                    :class="{ 'opacity-50 cursor-not-allowed': pageMode !== 'edit' }"
                                                    class="w-full px-1 py-1 text-sm text-right border rounded dark:text-gray-800"
                                                    min="0"
                                                />
                                            </span>
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
                            class="dark:bg-lime-500 dark:hover:bg-lime-700"
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
                    class="p-4 border-2 rounded-lg shadow-lg border-lime-100 bg-lime-50 dark:bg-lime-900/30"
                >
                    <h3
                        class="flex justify-between mb-2 text-lg font-bold text-yellow-800 dark:text-yellow-200"
                    >
                        Detail Nota Target
                        <span
                        :class="{
                            'px-2 py-1 text-white rounded': true,
                            'bg-green-600': invoice.payment_status === 'paid',
                            'bg-yellow-600': invoice.payment_status === 'partial',
                            'bg-red-600': invoice.payment_status === 'unpaid',
                        }"
                        class="items-center text-xs uppercase"
                >
                    {{ invoice.payment_status }}
                </span>
                    </h3>
                    <div class="text-sm dark:text-gray-300">
                        <p class="flex justify-between pb-2 mb-1 border-b-2 border-lime-300">
                            <strong>Supplier : </strong>
                            <div class="flex flex-col">
                                <span class="font-medium">{{
                                    purchase.supplier
                                        ? purchase.supplier.name
                                        : "Umum/Cash"
                                }}</span>
                                <span class="text-sm text-gray-500">{{
                                    purchase.supplier
                                        ? purchase.supplier.address +
                                          " | " +
                                          purchase.supplier.phone
                                        : "-"
                                }}</span>
                            </div>
                        </p>
                        <p class="flex justify-between">
                            <strong>Total Nominal Nota:</strong>
                            <span class="text-xl font-extrabold text-red-600">{{
                                formatRupiah(invoice.total_amount)
                            }}</span>
                        </p>
                        <p class="flex justify-between">
                            <strong>Subtotal Produk ditautkan :</strong>
                            <span class="font-bold text-green-700">{{
                                formatRupiah(computedTotalNominal)
                            }}</span>
                        </p>
                        <p class="flex justify-between">
                            <strong>Jumlah Produk ditautkan :</strong>
                            <span class="font-bold text-green-700">{{
                                computedTotalQty
                            }}</span>
                        </p>
                        <p class="flex justify-between">
                            Diterbitkan pada :
                            <span>
                                {{ formatTanggal(invoice.invoice_date) }}
                            </span>
                        </p>
                        <p class="flex justify-between">
                            Jatuh Tempo pada :
                            <span>
                                {{ formatTanggal(invoice.due_date) }}
                            </span>
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
                    class="p-4 border-2 rounded-lg shadow-lg border-lime-100 bg-lime-50 dark:bg-lime-900/30"
                >
                    <h4 class="mb-3 font-bold text-yellow-800 dark:text-yellow-200">
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

                <div class="p-6 bg-white border-2 border-gray-100 rounded-lg shadow-lg dark:bg-gray-800">
                    <h4 class="mb-3 font-bold dark:text-gray-200">
                        Produk Belum Tertaut
                    </h4>
                    <form @submit.prevent="submitLinkage">
                        <p
                            v-if="pageMode.value === 'edit' && unlinkedItems.length === 0"
                            class="py-4 text-center text-gray-500"
                        >
                            Semua produk sudah ditautkan.
                        </p>
                        <p
                            v-if="pageMode.value !== 'edit'"
                            class="py-4 text-center text-gray-500"
                        >
                            Pembelian sudah divalidasi.
                        </p>
                        <div
                            v-else
                            class="overflow-y-auto border rounded max-h-48 dark:border-gray-700"
                        >
                            <ul class="p-2 space-y-1">
                                <li
                                    v-for="item in unlinkedItems"
                                    :key="item.id"
                                    class="flex items-center justify-between w-full p-2 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <label
                                        :for="'link_item_' + item.id"
                                        class="flex items-start w-full gap-2 text-sm cursor-pointer dark:text-gray-300"
                                    >
                                        <input
                                            :id="'link_item_' + item.id"
                                            type="checkbox"
                                            :value="item.product_id"
                                            v-model="selectedLinkItemIds"
                                            class="rounded text-lime-600 mt-1.5"
                                        />

                                        <div class="flex flex-col flex-1">
                                            <div
                                                class="font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ item.product.name }} ({{
                                                    item.product.code
                                                }})
                                            </div>

                                            <div
                                                class="flex items-center justify-between mt-1 text-xs"
                                            >
                                                <span
                                                    class="text-gray-600 dark:text-gray-400"
                                                >
                                                    ({{ item.quantity }}x) @{{
                                                        formatRupiah(
                                                            item.purchase_price
                                                        )
                                                    }}
                                                </span>

                                                <span
                                                    class="font-bold text-gray-800 dark:text-gray-200"
                                                >
                                                    Subtotal:
                                                    {{
                                                        formatRupiah(
                                                            item.subtotal
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
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
                                Tautkan {{ selectedLinkItemIds.length }} Item
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
