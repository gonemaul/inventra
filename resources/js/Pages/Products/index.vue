<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import FilterModal from "./partials/modalFilter.vue";
import CardProduct from "./partials/card-product.vue";
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
            <Pagination
                v-if="products.data.length > 10"
                :metadata="products"
                :filters="filters"
            />
            <div
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
                <CardProduct
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
            <Pagination
                v-if="products.data.length > 8"
                :metadata="products"
                :filters="filters"
            />
        </div>
    </AuthenticatedLayout>
</template>
