<script setup>
// main
import { Deferred, Head, router } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import { throttle } from "lodash";
import { useActionLoading } from "@/Composable/useActionLoading";
// Main view
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FilterModal from "./partials/modalFilter.vue";
import Pagination from "./partials/pagination.vue";
import Filter from "@/Components/Filter.vue";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import ImageModal from "@/Components/ImageModal.vue";
import BottomSheet from "@/Components/BottomSheet.vue";
// Product view
import ProductCardList from "./Components/Desktop/ProductCardList.vue";
import ProductCardGrid from "./Components/Desktop/ProductCardGrid.vue";
import ProductKanbanBoard from "./Components/Desktop/ProductKanbanBoard.vue";
import EmptyState from "./Components/EmptyState.vue";
import StockAdjustmentForm from "./Components/Mobile/StockAdjustmentSheet.vue";
import PriceAdjustmentForm from "./Components/Mobile/PriceAdjustmentSheet.vue";
import ProductDetailSheet from "./Components/Mobile/ProductDetailSheet.vue";
// Child
import MobileCardGrid from "./Components/Mobile/MobileCardGrid.vue";

const props = defineProps({
    products: Object, // Berisi data produk yang sudah dipaginasi
    filters: Object,
    dropdowns: Object,
});
const { isActionLoading } = useActionLoading();
const configSheet = ref({
    title: "Detail Produk",
    data: {},
});
// state view
const viewMode = ref("grid"); // Opsi: 'grid', 'list', 'kanban'
const modalMode = ref("detail"); // Opsi: detail, stok, price
const showFilterModal = ref(false); // modal filter
const showConfirmModal = ref(null); //delete
const showBottomSheet = ref(false); // modal bottom sheet
// state filter
const search = ref(props.filters.search || "");
const isTrashView = computed(() => {
    return props.filters.trashed === true || props.filters.trashed === "true";
});
const activeFilterCount = computed(() => {
    const filterKeys = Object.keys(props.filters);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});
// image
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedProductName = ref(null);
// State Deteksi Layar
const isMobile = ref(window.innerWidth < 1024);
const updateScreenSize = () => {
    isMobile.value = window.innerWidth < 1024;
};

const BADGE_CONFIG = {
    restock: {
        label: "🚨 Stok",
        class: "bg-red-500 animate-pulse",
        priority: 1,
    },
    margin_alert: { label: "⚠️ Margin", class: "bg-yellow-400", priority: 2 },
    dead_stock: { label: "🐢 Slow", class: "bg-gray-600", priority: 3 },
    trend: {
        label: "🔥 Trending",
        class: "bg-purple-600 animate-pulse",
        priority: 4,
    },
    new: { label: "✨ New", class: "bg-lime-500", priority: 5 },
    high_margin: { label: "💰 Cuan", class: "bg-emerald-600", priority: 6 },
    promo: { label: "% Promo", class: "bg-blue-500", priority: 7 },
};
const productsWithBadges = computed(() => {
    return props.products.data.map((product) => {
        // Proses badge untuk setiap produk
        const active_badges = (product.insights || [])
            .map((insight) => ({
                ...BADGE_CONFIG[insight.type],
                type: insight.type,
            }))
            .filter((badge) => badge.label)
            .sort((a, b) => a.priority - b.priority)
            .slice(0, 2); // Opsi B: Ambil 2 saja

        // Return produk yang sudah ditempeli badge hasil olahan
        return { ...product, active_badges };
    });
});
const openOpsiSheet = (mode, item = null) => {
    modalMode.value = mode;
    if (item != null) {
        configSheet.value = {};
        configSheet.value.data = item;
    }
    if (mode == "detail") {
        configSheet.value.title = "Detail Produk";
    } else if (mode == "stock") {
        configSheet.value.title = "Penyesuaian Stock";
    } else if (mode == "price") {
        configSheet.value.title = "Penyesuaian Harga";
    }
    showBottomSheet.value = true;
};
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
                id: product.slug,
                permanen: true,
            }),
        };
    } else {
        config = {
            title: "Pindahkan ke Sampah",
            message: "Anda yakin ingin memindahkan produk",
            itemName: product.name,
            url: route("products.destroy", { id: product.slug }),
        };
    }
    showConfirmModal.value.open(config);
};
const restoreProduct = (row) => {
    isActionLoading.value = true;

    router.put(
        route("products.restoreProduct", { id: row.slug }),
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
    if (!currentFilters.search || search.value == "") {
        delete currentFilters.search;
    }
    router.get(route("products.index"), currentFilters, {
        preserveState: true,
        replace: true,
    });
}, 300);
watch(search, performSearch);
const isFiltering = computed(() => {
    // Sesuaikan dengan props filters Anda
    return props.filters;
});
const sortCustom = (sort) => {
    const currentFilters = { ...props.filters };
    currentFilters.sort = sort;
    console.log(sort)
    if (sort === 'default') {
        delete currentFilters.sort;
    }
    isActionLoading.value = true;
    router.get(
        route("products.index"),
        currentFilters,
        {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isActionLoading.value = false;
            },
        }
    );
}
const resetFilter = () => {
    isActionLoading.value = true;
    router.get(
        route("products.index"),
        { per_page: props.filters.per_page || 10 },
        {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isActionLoading.value = false;
            },
        }
    );
};
onMounted(() => window.addEventListener("resize", updateScreenSize));
onUnmounted(() => window.removeEventListener("resize", updateScreenSize));
</script>
<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedProductName"
        @close="showImageModal = false"
    />
    <DeleteConfirm ref="showConfirmModal" @success="" />
    <Head title="Data Barang" />
    <AuthenticatedLayout headerTitle="Data Barang">
        <div class="w-full pb-10">
            <Deferred data="dropdowns">
                <FilterModal
                    :show="showFilterModal"
                    @close="showFilterModal = false"
                    :filters="filters"
                    :dropdowns="dropdowns"
                />
                <template #fallback>
                    <div class=""></div>
                </template>
            </Deferred>
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
                class="flex gap-1 p-1 my-3 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700"
            >
                <button
                    @click="viewMode = 'grid'"
                    :class="
                        viewMode === 'grid'
                            ? 'bg-lime-500 text-white'
                            : 'text-gray-400 hover:bg-gray-100 hover:text-lime-500'
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
                            : 'text-gray-400 hover:bg-gray-100 hover:text-lime-500'
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
                            : 'text-gray-400 hover:bg-gray-100 hover:text-lime-500'
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

                <div
                class="flex items-center gap-2 pl-2 border-l border-gray-300 dark:border-gray-700 shrink-0"
            >
                <button
                    @click="
                        sortCustom(
                            filters.sort === 'cheapest' ? 'default' : 'cheapest'
                        )
                    "
                    :class="
                        filters.sort === 'cheapest'
                            ? 'bg-orange-100 text-orange-700 border-orange-200'
                            : 'bg-white text-gray-400 border-gray-200'
                    "
                    class="p-1.5 border rounded-lg transition-all flex items-center gap-2 font-medium text-sm"
                    title="Termurah"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 20V10" />
                        <path d="M18 20V4" />
                        <path d="M6 20v-4" />
                    </svg>
                    {{ filters.sort === 'cheapest' ? 'Termurah' : '' }}
                </button>
                <button
                    @click="
                        sortCustom(
                            filters.sort === 'bestseller' ? 'default' : 'bestseller'
                        )
                    "
                    :class="
                        filters.sort === 'bestseller'
                            ? 'bg-purple-100 text-purple-700 border-purple-200'
                            : 'bg-white text-gray-400 border-gray-200'
                    "
                    class="p-1.5 border rounded-lg transition-all flex gap-2 items-center font-medium text-sm"
                    title="Terlaris"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                        <path d="M4 22h16" />
                        <path
                            d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"
                        />
                        <path
                            d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"
                        />
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                    </svg>
                    {{ filters.sort === 'bestseller' ? 'Terlaris' : '' }}
                </button>
            </div>
            </div>
            <Pagination
                v-if="products.data.length > 10"
                :metadata="products"
                :filters="filters"
                class="mb-3"
            />
            <div class="" v-if="products.data.length > 0">
                <div
                    v-if="viewMode === 'grid'"
                    class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                >
                    <MobileCardGrid
                        v-if="isMobile"
                        v-for="product in productsWithBadges"
                        :key="'mobile' + product.id"
                        :data="product"
                        @click="openOpsiSheet('detail', product)"
                        @imageClick="openImageModal"
                    />
                    <ProductCardGrid
                        v-else
                        v-for="product in productsWithBadges"
                        :key="'desktop' + product.id"
                        :data="product"
                        @delete="openDeleteModal(product)"
                        @forceDelete="openDeleteModal(product, true)"
                        @restore="restoreProduct(product)"
                        @imageClick="openImageModal"
                        @adjustStock="openOpsiSheet('stock', product)"
                        @adjustPrice="openOpsiSheet('price', product)"
                    />
                </div>
                <div
                    v-else-if="viewMode === 'list'"
                    class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <ProductCardList
                        v-for="product in productsWithBadges"
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
                    <ProductKanbanBoard :products="productsWithBadges" />
                </div>
            </div>
            <div v-else class="mt-8">
                <EmptyState
                    v-if="isFiltering"
                    title="Tidak ditemukan"
                    message="Maaf, tidak ada produk yang cocok dengan pencarian atau filter Anda. Coba atur ulang."
                    icon="search"
                    action-label="Reset Filter"
                    action-type="button"
                    @action="resetFilter"
                />
                <EmptyState
                    v-else
                    title="Belum ada Produk"
                    message="Toko Anda masih kosong. Mulai tambahkan produk pertama Anda sekarang!"
                    icon="box"
                    action-label="Tambah Produk Baru"
                    :action-url="route('products.create')"
                    action-type="link"
                />
            </div>
            <Pagination
                v-if="products.total > 8"
                :metadata="products"
                :filters="filters"
            />
        </div>
    </AuthenticatedLayout>
    <BottomSheet
        :show="showBottomSheet"
        @close="showBottomSheet = false"
        :title="configSheet.title"
    >
        <ProductDetailSheet
            v-if="modalMode == 'detail'"
            :data="configSheet.data"
            @adjustStock="openOpsiSheet('stock')"
            @adjustPrice="openOpsiSheet('price')"
            @delete="openDeleteModal"
        />
        <StockAdjustmentForm
            v-else-if="modalMode == 'stock'"
            :product="configSheet.data"
            @close="showBottomSheet = false"
            @success="
                {
                    (showBottomSheet = false), (modalMode = 'detail');
                }
            "
        />
        <PriceAdjustmentForm
            v-else-if="modalMode == 'price'"
            :product="configSheet.data"
            @close="showBottomSheet = false"
            @success="
                {
                    (showBottomSheet = false), (modalMode = 'detail');
                }
            "
        />
    </BottomSheet>
</template>
