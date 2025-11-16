<script setup>
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";

const props = defineProps({
    dropdowns: Object, // Berisi { categories: [], units: [], sizes: [], suppliers: [], productStatuses: [] }
    product: {
        type: Object,
        default: null, // Akan 'null' saat mode 'create'
    },
    mode: String, // 'create' or 'edit'
});
const { isActionLoading } = useActionLoading();
const form = useForm({
    // Relasi
    category_id: props.product?.category_id || null,
    unit_id: props.product?.unit_id || null,
    size_id: props.product?.size_id || null,
    supplier_id: props.product?.supplier_id || null,

    // Detail
    name: props.product?.name || "",
    code: props.product?.code || "",
    description: props.product?.description || "",
    status: props.product?.status || "active", // Default 'active' saat create

    // Harga & Stok (sesuai nama kolom DB)
    purchase_price: props.product?.purchase_price || 0,
    selling_price: props.product?.selling_price || 0,
    stock: props.product?.stock || 0,
    min_stock: props.product?.min_stock || 0,

    // File (image akan berisi file baru, image_path untuk preview)
    image: null,
});
const imagePreview = ref(
    props.product?.image_path ? `/storage/${props.product.image_path}` : null
);
function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
        form.image = file; // Simpan file di form
        imagePreview.value = URL.createObjectURL(file); // Buat preview lokal
    }
}
const submitForm = () => {
    isActionLoading.value = true; // Nyalakan loader global

    if (props.mode === "create") {
        form.post(route("products.store"), {
            onFinish: () => (isActionLoading.value = false),
        });
    } else {
        form.transform((data) => ({
            ...data,
            _method: "put",
        })).post(route("products.update", { product: props.product.id }), {
            onFinish: () => (isActionLoading.value = false),
        });
    }
};
</script>

<template>
    <form
        class="p-6 bg-gray-100 rounded-lg shadow dark:bg-customBg-tableDark"
        @submit.prevent="submitForm"
    >
        <!-- Upload + Kode Barang -->
        <div class="flex flex-col gap-4 md:flex-row">
            <!-- Upload -->
            <div class="flex flex-col lg:w-2/5">
                <label
                    for="image-upload"
                    :class="[
                        'flex flex-col items-center justify-center w-40 mt-2 rounded-lg cursor-pointer aspect-square bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600',
                        {
                            'border-2 border-gray-300 border-dashed':
                                !imagePreview,
                        },
                    ]"
                >
                    <img
                        v-if="imagePreview"
                        :src="imagePreview"
                        alt="Preview"
                        class="object-cover w-full h-full rounded-lg"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center justify-center w-full h-full pt-5 pb-6"
                    >
                        <p
                            class="mb-2 text-sm text-gray-500 dark:text-gray-200"
                        >
                            <span class="font-semibold">Klik untuk Upload</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-200">
                            WEBP, PNG (max. 1MB)
                        </p>
                    </div>
                    <input
                        id="image-upload"
                        type="file"
                        name="image"
                        class="hidden"
                        @change="handleFileChange"
                    />
                </label>
                <p v-if="form.errors.image" class="mt-1 text-sm text-red-500">
                    {{ form.errors.image }}
                </p>
            </div>

            <!-- Input kode -->
            <div class="flex flex-col justify-center flex-1 gap-3">
                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                        >Kode Barang</label
                    >
                    <TextInput
                        class="w-full max-h-10"
                        id="kode"
                        type="text"
                        name="kode"
                        placeholder="Kode Barang"
                        v-model="form.code"
                    />
                    <p
                        v-if="form.errors.code"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>
                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                        >Nama Barang<span class="text-red-500">*</span></label
                    >
                    <TextInput
                        class="w-full max-h-10"
                        id="nama"
                        type="text"
                        name="nama"
                        placeholder="Nama Barang"
                        v-model="form.name"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Selects -->
        <div class="grid grid-cols-1 gap-3 mt-5 md:grid-cols-4">
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Kategori<span class="text-red-500">*</span></label
                >
                <select
                    class="w-full px-3 py-2 text-sm border border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                    v-model="form.category_id"
                >
                    <option v-if="props.mode == 'create'" :value="null">
                        Pilih Kategori
                    </option>
                    <option
                        v-for="cat in dropdowns.categories"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
                <p
                    v-if="form.errors.category_id"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.category_id }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Satuan<span class="text-red-500">*</span></label
                >
                <select
                    v-model="form.unit_id"
                    class="w-full px-3 py-2 text-sm border border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option v-if="props.mode == 'create'" :value="null">
                        Pilih Satuan
                    </option>
                    <option
                        v-for="unit in dropdowns.units"
                        :key="unit.id"
                        :value="unit.id"
                    >
                        {{ unit.name }}
                    </option>
                </select>
                <p v-if="form.errors.unit_id" class="mt-1 text-sm text-red-500">
                    {{ form.errors.unit_id }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Ukuran<span class="text-red-500">*</span></label
                >
                <select
                    v-model="form.size_id"
                    class="w-full px-3 py-2 text-sm border border-gray-400 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option v-if="props.mode == 'create'" :value="null">
                        Pilih Ukuran
                    </option>
                    <option
                        v-for="size in dropdowns.sizes"
                        :key="size.id"
                        :value="size.id"
                    >
                        {{ size.name }}
                    </option>
                </select>
                <p v-if="form.errors.size_id" class="mt-1 text-sm text-red-500">
                    {{ form.errors.size_id }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Supplier<span class="text-red-500">*</span></label
                >
                <select
                    v-model="form.supplier_id"
                    class="w-full px-3 py-2 text-sm border border-gray-400 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option v-if="props.mode == 'create'" :value="null">
                        Pilih Supplier
                    </option>
                    <option
                        v-for="supplier in dropdowns.suppliers"
                        :key="supplier.id"
                        :value="supplier.id"
                    >
                        {{ supplier.name }}
                    </option>
                </select>
                <p
                    v-if="form.errors.supplier_id"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.supplier_id }}
                </p>
            </div>
        </div>

        <!-- Deskripsi -->
        <div class="mt-4">
            <label
                class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                >Deskripsi<span class="text-red-500">*</span></label
            >
            <textarea
                v-model="form.description"
                name="description"
                id="description"
                rows="3"
                placeholder="Deskripsi Barang"
                class="w-full px-3 py-2 border border-gray-400 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">
                {{ form.errors.description }}
            </p>
        </div>

        <!-- Harga Beli & Jual -->
        <div class="flex flex-col gap-3 mt-4 md:flex-row">
            <div class="flex-1">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Harga Beli<span class="text-red-500">*</span></label
                >
                <TextInput
                    v-model="form.purchase_price"
                    class="w-full max-h-10"
                    id="price_buy"
                    type="number"
                    name="price_buy"
                    placeholder="Harga Beli"
                />
                <p
                    v-if="form.errors.purchase_price"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.purchase_price }}
                </p>
            </div>
            <div class="flex-1">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Harga Jual<span class="text-red-500">*</span></label
                >
                <TextInput
                    v-model="form.selling_price"
                    class="w-full max-h-10"
                    id="price_sell"
                    type="number"
                    name="price_sell"
                    placeholder="Harga Jual"
                />
                <p
                    v-if="form.errors.selling_price"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.selling_price }}
                </p>
            </div>
        </div>

        <!-- Stock -->
        <div class="grid grid-cols-1 gap-3 mt-4 md:grid-cols-3">
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 border-gray-400 dark:text-gray-100"
                    >Stock<span class="text-red-500">*</span></label
                >
                <TextInput
                    class="w-full max-h-10"
                    id="stock"
                    type="number"
                    name="stock"
                    placeholder="Stock"
                    v-model="form.stock"
                />
                <p v-if="form.errors.stock" class="mt-1 text-sm text-red-500">
                    {{ form.errors.stock }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 border-gray-400 dark:text-gray-100"
                    >Min Stock<span class="text-red-500">*</span></label
                >
                <TextInput
                    v-model="form.min_stock"
                    class="w-full max-h-10"
                    id="min_stock"
                    type="number"
                    name="min_stock"
                    placeholder="Min Stock"
                />
                <p
                    v-if="form.errors.min_stock"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.min_stock }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Status<span class="text-red-500">*</span></label
                >
                <select
                    v-model="form.status"
                    class="w-full px-3 py-2 text-sm border border-gray-400 rounded-lg focus:border-lime-500 focus:ring-lime-500 dark:bg-gray-700 dark:text-white"
                >
                    <option
                        v-for="status in dropdowns.productStatuses"
                        :key="status"
                        :value="status"
                    >
                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                    </option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                    {{ form.errors.status }}
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6">
            <Link :href="route('products.index')">
                <SecondaryButton>Batal</SecondaryButton>
            </Link>
            <PrimaryButton type="submit" :disabled="form.processing">
                {{ form.processing ? "Menyimpan..." : "Simpan" }}
            </PrimaryButton>
        </div>
    </form>
</template>
