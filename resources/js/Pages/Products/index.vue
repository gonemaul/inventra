<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import FilterModal from "./partials/modalFilter.vue";
import ProductCardList from "./partials/ProductCardList.vue";
import ProductCardGrid from "./partials/ProductCardGrid.vue";
import ProductKanbanBoard from "./partials/ProductKanbanBoard.vue";
import Pagination from "./partials/pagination.vue";
import { ref, watch, computed } from "vue";
import Filter from "@/Components/Filter.vue";
import { throttle } from "lodash";
import { useActionLoading } from "@/Composable/useActionLoading";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import ImageModal from "@/Components/ImageModal.vue";

const props = defineProps({
    products: Object, // Berisi data produk yang sudah dipaginasi
    filters: Object,
    dropdowns: Object,
});
const viewMode = ref("grid"); // Opsi: 'grid', 'list', 'kanban'
const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const { isActionLoading } = useActionLoading();
const showConfirmModal = ref(null);
// image
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedProductName = ref(null);
// sampah
const isTrashView = computed(() => {
    return props.filters.trashed === true || props.filters.trashed === "true";
});
const activeFilterCount = computed(() => {
    const filterKeys = Object.keys(props.filters);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});

const openImageModal = (payload) => {
    selectedImageUrl.value = payload.path;
    selectedProductName.value = payload.name;
    showImageModal.value = true;
};

const openDeleteModal = (product, isPermanent = false) => {
    let config = {};
    if (isPermanent) {
        config = {
            title: "Hapus Permanen Produk",
            message: "Produk ini akan dihapus selamanya. Anda yakin menghapus",
            itemName: product.name,
            url: route("products.destroy", {
                id: product.id,
                permanen: true,
            }),
        };
    } else {
        config = {
            title: "Pindahkan ke Sampah",
            message: "Anda yakin ingin memindahkan produk",
            itemName: product.name,
            url: route("products.destroy", { id: product.id }),
        };
    }
    showConfirmModal.value.open(config);
};
const restoreProduct = (row) => {
    isActionLoading.value = true;

    router.put(
        route("products.restoreProduct", { id: row.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Tindakan setelah berhasil memulihkan
            },
            onError: (errors) => {
                console.error(errors);
            },
            onFinish: () => {
                isActionLoading.value = false;
            },
        }
    );
};
const performSearch = throttle(() => {
    const currentFilters = { ...props.filters };
    currentFilters.search = search.value;
    if (!currentFilters.search) {
        delete currentFilters.search;
    }
    router.get(route("products.index"), currentFilters, {
        preserveState: true,
        replace: true,
    });
}, 300);
watch(search, performSearch);
</script>
<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedProductName"
        @close="showImageModal = false"
    />
    <DeleteConfirm ref="showConfirmModal" @success="" />
    <Head title="Data Barang" /><AuthenticatedLayout headerTitle="Data Barang"
        ><div class="w-full min-h-screen">
            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
                :dropdowns="dropdowns"
            />
            <Filter
                :filters="filters"
                v-model="search"
                @showFilter="showFilterModal = true"
                :filterCount="activeFilterCount"
                :actions="[
                    {
                        route: route('products.create'),
                        buttonText: 'Tambah Produk',
                    },
                ]"
            />
            <div
                class="flex p-1 my-3 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700"
            >
                <button
                    @click="viewMode = 'grid'"
                    :class="
                        viewMode === 'grid'
                            ? 'bg-lime-500 text-white'
                            : 'text-gray-400 hover:bg-gray-100'
                    "
                    class="p-2 transition rounded"
                    title="Mode Grid"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                        />
                    </svg>
                </button>

                <button
                    @click="viewMode = 'list'"
                    :class="
                        viewMode === 'list'
                            ? 'bg-lime-500 text-white'
                            : 'text-gray-400 hover:bg-gray-100'
                    "
                    class="p-2 transition rounded"
                    title="Mode Daftar"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>

                <button
                    @click="viewMode = 'kanban'"
                    :class="
                        viewMode === 'kanban'
                            ? 'bg-lime-500 text-white'
                            : 'text-gray-400 hover:bg-gray-100'
                    "
                    class="p-2 transition rounded"
                    title="Mode Analisa DSS"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                        />
                    </svg>
                </button>
            </div>
            <Pagination
                v-if="products.data.length > 10"
                :metadata="products"
                :filters="filters"
            />
            <div
                v-if="viewMode === 'grid'"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="h-full"
                >
                    <ProductCardGrid
                        :data="product"
                        @delete="openDeleteModal(product, false)"
                        @imageClick="openImageModal"
                    />
                </div>
            </div>
            <div
                v-else-if="viewMode === 'list'"
                class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-1 lg:grid-cols-2"
            >
                <div
                    v-if="products.data.length === 0"
                    class="col-span-full text-center ..."
                >
                    <p class="text-gray-500 ...">
                        Tidak ada produk yang ditemukan.
                    </p>
                </div>
                <ProductCardList
                    v-for="product in products.data"
                    :is-trash-view="isTrashView"
                    :key="product.id"
                    :data="product"
                    @delete="openDeleteModal(product, false)"
                    @forceDelete="openDeleteModal(product, true)"
                    @restore="restoreProduct(product)"
                    @imageClick="openImageModal"
                />
            </div>
            <div v-else-if="viewMode === 'kanban'">
                <ProductKanbanBoard :products="products.data" />
            </div>
            <Pagination
                v-if="products.data.length > 8"
                :metadata="products"
                :filters="filters"
            />
        </div>
    </AuthenticatedLayout>
</template>
