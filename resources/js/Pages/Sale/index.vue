<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import DataTable from "@/Components/DataTable.vue";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { throttle } from "lodash";
import ModalInvoice from "./Components/ModalInvoice.vue";
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    filters: Object,
    sales: Object,
});

const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const { isActionLoading } = useActionLoading();
const showInvoice = ref(false);
const isLoading = ref(false);
const invoiceHtml = ref("");
const selectedId = ref(null);
const selectReference_no = ref("-");

const columns = [
    {
        key: "transaction_date",
        label: "Tanggal",
        sortable: true,
        format: "tanggal",
        width: "130px",
    },
    {
        key: "reference_no",
        label: "No. Referensi",
        sortable: true,
        slot: "reference",
        width: "130px",
    },
    {
        key: "summary",
        label: "Summary",
        slot: "Summary",
        width: "130px",
    },
    {
        key: "total_revenue",
        label: "Total Omset",
        sortable: true,
        format: "rupiah",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.sales.current_page || 1, // Penting untuk pagination
});

const refreshTable = () => {
    router.reload({ only: ["sales", "filters"], preserveScroll: true });
};

const performSearch = throttle(() => {
    const currentFilters = { ...props.filters };
    currentFilters.search = search.value;
    if (!currentFilters.search) {
        delete currentFilters.search;
    }
    router.get(route("sales.index"), currentFilters, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(
    params,
    throttle((newParams) => {
        const query = Object.fromEntries(
            Object.entries(newParams).filter(([_, v]) => v != null && v !== "")
        );
        isActionLoading.value = true;
        router.get(route("sales.index"), query, {
            preserveState: true, // Jangan scroll ke atas
            preserveScroll: true,
            replace: true, // Jangan penuh-penuhin history browser
            only: ["sales", "filters"], // Optimasi: Cuma update data tabel
            onFinish: () => {
                isActionLoading.value = false;
            },
        });
    }, 300),
    { deep: true }
);

watch(search, performSearch);

const activeFilterCount = computed(() => {
    const query = Object.fromEntries(
        Object.entries(params).filter(([_, v]) => v != null && v !== "")
    );
    const filterKeys = Object.keys(props.filters);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});
const openInvoice = async (row) => {
    selectedId.value = row.id;
    selectReference_no.value = row.reference_no;
    showInvoice.value = true;
    isLoading.value = true;
    invoiceHtml.value = ""; // Reset dulu

    try {
        const response = await axios.get(`/sales/${row.id}/print`, {
            responseType: "text",
        });

        invoiceHtml.value = response.data; // Isi HTML
    } catch (error) {
        console.error("Gagal load invoice:", error);
        invoiceHtml.value =
            '<p class="mt-10 text-center text-red-500">Gagal memuat data invoice.</p>';
    } finally {
        isLoading.value = false; // Matikan loading
    }
};
const handlePrint = () => {
    if (!invoiceHtml.value) return;

    // Trik: Buka window popup kosong, tulis HTML blade di sana, lalu print
    const printWindow = window.open("", "", "height=600,width=400");

    printWindow.document.write("<html><head><title>Print</title>");
    // Opsional: Tambahkan CSS reset agar print rapi
    printWindow.document.write(
        "<style>body { margin: 0; padding: 0; }</style>"
    );
    printWindow.document.write("</head><body>");

    // Tulis HTML dari Blade ke window baru
    printWindow.document.write(invoiceHtml.value);

    printWindow.document.write("</body></html>");
    printWindow.document.close();
    printWindow.focus();

    // Beri jeda dikit biar gambar/font ke-load, lalu print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
};
</script>

<template>
    <Head title="Riwayat Penjualan" />

    <AuthenticatedLayout headerTitle="Riwayat Penjualan">
        <div class="w-full min-h-screen space-y-6">
            <Filter
                :filters="filters"
                v-model="search"
                @showFilter="showFilterModal = true"
                :filterCount="activeFilterCount"
                :actions="[
                    { route: route('sales.create'), buttonText: 'Rekap' },
                ]"
            />
            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
            />

            <div
                class="p-4 space-y-6 bg-gray-100 border shadow-md rounded-xl dark:bg-customBg-tableDark"
            >
                <!-- Title & Description -->
                <div>
                    <h2
                        class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                    >
                        Riwayat Penjualan
                    </h2>
                    <p
                        class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                    >
                        Kelola semua riwayat penjualan.
                    </p>
                </div>
                <!-- Data Table -->
                <DataTable
                    serverSide="true"
                    :data="sales.data"
                    :columns="columns"
                    :perPageOptions="[5, 10, 25, 50, 100]"
                    v-model:params="params"
                    :pagination="sales"
                >
                    <template #reference="{ row }">
                        <div
                            @click="openInvoice(row)"
                            class="font-mono text-sm font-bold text-indigo-600 dark:text-indigo-400"
                        >
                            {{ row.reference_no }}
                        </div>
                        <div
                            v-if="row.notes"
                            class="text-xs text-gray-500 truncate max-w-[150px]"
                            :title="row.notes"
                        >
                            📝 {{ row.notes }}
                        </div>
                    </template>
                    <template #Summary="{ row }">
                        <div
                            v-if="row.financial_summary"
                            class="my-4 text-sm text-gray-600 dark:text-gray-200 whitespace-nowrap"
                        >
                            <div>
                                Item:
                                <strong>{{
                                    row.financial_summary.item_count
                                }}</strong>
                                Jenis
                            </div>
                            <div>
                                Qty:
                                <strong>{{
                                    row.financial_summary.total_qty
                                }}</strong>
                                Unit
                            </div>
                        </div>
                        <span v-else class="text-xs text-gray-400">-</span>
                    </template>
                    <template #aksi="{ row }">
                        <Link
                            :href="route('sales.show', row.id)"
                            class="px-3 py-1 text-sm font-medium text-indigo-600 transition border border-indigo-200 rounded hover:text-indigo-900 hover:bg-indigo-300"
                        >
                            Lihat Detail
                        </Link>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
    <BottomSheet
        :show="showInvoice"
        @close="showInvoice = false"
        :title="`Preview Invoice #` + selectReference_no"
    >
        <div class="flex flex-col h-full px-4">
            <!-- <h3 class="pb-2 mb-4 text-lg font-bold text-center border-b">
                Preview Invoice
            </h3> -->

            <div
                v-if="isLoading"
                class="flex-1 flex flex-col items-center justify-center min-h-[300px]"
            >
                <svg
                    class="w-10 h-10 mb-3 text-blue-500 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v8H4z"
                    ></path>
                </svg>
                <p class="text-lime-500">Memuat Invoice...</p>
            </div>

            <div v-else class="flex flex-col flex-1 overflow-hidden">
                <div
                    class="flex-1 p-2 mb-4 overflow-y-auto bg-gray-100 border rounded"
                >
                    <div
                        class="bg-white shadow-sm p-2 min-h-[200px] receipt-preview-wrapper"
                    >
                        <div v-html="invoiceHtml"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 mt-auto border-t">
                    <a
                        :href="`/sales/${selectedId}/print`"
                        target="_blank"
                        class="flex items-center justify-center gap-2 py-3 font-bold text-white transition bg-red-500 rounded-lg hover:bg-red-600"
                    >
                        📥 Download PDF
                    </a>

                    <button
                        @click="handlePrint"
                        class="flex items-center justify-center gap-2 py-3 font-bold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        🖨 Print Struk
                    </button>
                </div>
            </div>
        </div>
    </BottomSheet>
</template>
<style scoped>
.receipt-preview-wrapper {
    /* Agar konten preview tidak terlalu lebar */
    max-width: 80mm;
    margin: 0 auto;
    font-family: "Courier New", Courier, monospace;
    font-size: 12px; /* Sesuaikan ukuran font preview */
}

/* Kustomisasi scrollbar bottom sheet biar cantik */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
</style>
