<script setup>
import { defineAsyncComponent } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";
const BarcodeScanner = defineAsyncComponent(() =>
    import("@/Components/BarcodeScanner.vue")
);

const props = defineProps({
    dropdowns: Object, // Berisi { categories: [], units: [], sizes: [], suppliers: [], productStatuses: [] }
    product: {
        type: Object,
        default: null, // Akan 'null' saat mode 'create'
    },
    mode: String, // 'create' or 'edit'
});
const { isActionLoading } = useActionLoading();
const showScanCode = ref(false);
const form = useForm({
    // Relasi
    category_id: props.product?.category_id || null,
    unit_id: props.product?.unit_id || null,
    size_id: props.product?.size_id || null,
    supplier_id: props.product?.supplier_id || null,
    brand_id: props.product?.brand_id || null,
    product_type_id: props.product?.product_type_id || null,

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
    target_margin_percent: props.product?.target_margin_percent || 20,
    margin_nominal: 0,

    // File (image akan berisi file baru, image_path untuk preview)
    image: null,
});
const imagePreview = ref(props.product?.image_url ?? null);

onMounted(async () => {
    const buy = parseFloat(form.purchase_price) || 0;
    const percent = parseFloat(form.target_margin_percent) || 0;
    const nominal = buy * (percent / 100);
    form.margin_nominal = Math.round(nominal);
});

const onPurchaseChange = () => {
    const buy = parseFloat(form.purchase_price) || 0;
    const percent = parseFloat(form.target_margin_percent) || 0;

    // Hitung Nominal dari %
    const nominal = buy * (percent / 100);
    form.margin_nominal = Math.round(nominal);

    // Hitung Harga Jual
    form.selling_price = Math.round(buy + nominal);
};

const onSellingChange = () => {
    const buy = parseFloat(form.purchase_price) || 0;
    const sell = parseFloat(form.selling_price) || 0;

    // Hitung Nominal (Selisih)
    const nominal = sell - buy;
    form.margin_nominal = nominal;

    // Hitung Persen
    if (buy > 0) {
        form.target_margin_percent = parseFloat(
            ((nominal / buy) * 100).toFixed(2)
        );
    } else {
        form.target_margin_percent = 100; // Default jika harga beli 0
    }
};

const onMarginPercentChange = () => {
    const buy = parseFloat(form.purchase_price) || 0;
    const percent = parseFloat(form.target_margin_percent) || 0;

    // Hitung Nominal
    const nominal = buy * (percent / 100);
    form.margin_nominal = Math.round(nominal);

    // Update Harga Jual
    form.selling_price = Math.round(buy + nominal);
};

const onMarginNominalChange = () => {
    const buy = parseFloat(form.purchase_price) || 0;
    const nominal = parseFloat(form.margin_nominal) || 0;

    // Update Harga Jual
    form.selling_price = Math.round(buy + nominal);

    // Hitung Persen
    if (buy > 0) {
        form.target_margin_percent = parseFloat(
            ((nominal / buy) * 100).toFixed(2)
        );
    }
};

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
    <BarcodeScanner
        v-if="showScanCode"
        @result="form.code"
        @close="showScanCode = false"
    />
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
                        onload="this.classList.remove('opacity-0')"
                        onerror="this.style.display='none'"
                        alt="Preview"
                        class="object-cover w-full h-full rounded-lg opacity-0"
                    />
                    <div
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
                <div class="relative flex-1">
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
                    <button
                        type="button"
                        @click="showScanCode = true"
                        class="absolute right-1.5 top-7 p-1 bg-white dark:bg-gray-700 rounded-lg shadow-sm text-gray-600 dark:text-gray-200 hover:text-lime-600 hover:bg-lime-50 transition border border-gray-200 dark:border-gray-600"
                        title="Scan Produk"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                            ></path>
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                            ></path>
                        </svg>
                    </button>
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
        <div class="grid grid-cols-1 gap-3 mt-5 md:grid-cols-3">
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
                    >Type<span class="text-red-500">*</span></label
                >
                <select
                    class="w-full px-3 py-2 text-sm border border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                    v-model="form.product_type_id"
                >
                    <option v-if="props.mode == 'create'" :value="null">
                        Pilih Type
                    </option>
                    <option
                        v-for="cat in dropdowns.types"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
                <p
                    v-if="form.errors.product_type_id"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.product_type_id }}
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
                    >Merk<span class="text-red-500">*</span></label
                >
                <select
                    v-model="form.brand_id"
                    class="w-full px-3 py-2 text-sm border border-gray-400 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option v-if="props.mode == 'create'" :value="null">
                        Pilih Merk
                    </option>
                    <option
                        v-for="size in dropdowns.brands"
                        :key="size.id"
                        :value="size.id"
                    >
                        {{ size.name }}
                    </option>
                </select>
                <p
                    v-if="form.errors.brand_id"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.brand_id }}
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
                    >Harga Beli (HPP)<span class="text-red-500">*</span></label
                >
                <div class="relative">
                    <span class="absolute text-sm text-gray-500 left-3 top-2"
                        >Rp</span
                    >
                    <TextInput
                        v-model="form.purchase_price"
                        class="w-full py-2 pr-3 max-h-10 pl-9"
                        @input="onPurchaseChange"
                        id="price_buy"
                        type="number"
                        min="0"
                        name="price_buy"
                        placeholder="Harga Beli"
                    />
                </div>
                <span class="flex justify-between">
                    <p class="ml-2 text-sm text-gray-500">
                        {{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.purchase_price) || 0
                        }}
                    </p>
                    <p
                        v-if="form.errors.purchase_price"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.purchase_price }}
                    </p>
                </span>
            </div>
            <div class="flex-1">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-100"
                    >Harga Jual<span class="text-red-500">*</span></label
                >
                <div class="relative">
                    <span class="absolute text-sm text-gray-500 left-3 top-2"
                        >Rp</span
                    >
                    <TextInput
                        v-model="form.selling_price"
                        class="w-full py-2 pr-3 max-h-10 pl-9"
                        id="price_sell"
                        type="number"
                        min="0"
                        @input="onSellingChange"
                        name="price_sell"
                        placeholder="Harga Jual"
                    />
                </div>
                <span class="flex justify-between">
                    <p class="ml-2 text-sm text-gray-500">
                        {{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.selling_price) || 0
                        }}
                    </p>
                    <p
                        v-if="form.errors.selling_price"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.selling_price }}
                    </p>
                </span>
            </div>
        </div>

        <!-- Stock -->
        <div class="grid grid-cols-1 gap-3 mt-4 md:grid-cols-5">
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
                    min="0"
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
                    min="0"
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
                    class="block mb-1 text-sm font-medium text-gray-700 border-gray-400 dark:text-gray-100"
                    >Margin (Rp)<span class="text-red-500">*</span></label
                >
                <div class="relative">
                    <span class="absolute text-sm text-gray-500 left-3 top-2"
                        >Rp</span
                    >
                    <TextInput
                        v-model="form.margin_nominal"
                        class="w-full py-2 pr-3 pl-9 max-h-10"
                        id="margin_nominal"
                        type="number"
                        min="0"
                        @input="onMarginNominalChange"
                        name="margin_nominal"
                        placeholder="Margin Nominal"
                    />
                    <p class="ml-2 text-sm text-gray-500">
                        {{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.margin_nominal) || 0
                        }}
                    </p>
                </div>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 border-gray-400 dark:text-gray-100"
                    >Margin (%)<span class="text-red-500">*</span></label
                >
                <div class="relative">
                    <TextInput
                        v-model="form.target_margin_percent"
                        class="w-full py-2 pl-3 pr-8 max-h-10"
                        id="target_margin_percent"
                        type="text"
                        min="0"
                        @input="onMarginPercentChange"
                        name="target_margin_percent"
                        placeholder="Margin Percent"
                    />
                    <span
                        class="absolute text-sm font-bold text-gray-500 right-3 top-2"
                        >%</span
                    >
                    <p
                        v-if="form.errors.target_margin_percent"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.target_margin_percent }}
                    </p>
                </div>
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
