<script setup>
import { defineAsyncComponent } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link, useForm, Deferred } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import BarcodeScanner from "@/Components/BarcodeScanner.vue";
import InputRupiah from "@/Components/InputRupiah.vue";

const props = defineProps({
    dropdowns: Object, // Berisi { categories: [], units: [], sizes: [], suppliers: [], productStatuses: [] }
    product: {
        type: Object,
        default: () => ({}), // Akan 'null' saat mode 'create'
    },
    mode: String, // 'create' or 'edit'
});
const { isActionLoading } = useActionLoading();
const showScanCode = ref(false);
const form = useForm({
    type: "",
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
    if (props.mode == "create") {
        form.selling_price = Math.round(buy + nominal);
    }
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
        form.type = "full";
        form.transform((data) => ({
            ...data,
            _method: "put",
        })).post(route("products.update", { product: props.product.id }), {
            onFinish: () => (isActionLoading.value = false),
        });
    }
};

const onScanResult = (decodedText) => {
    // Matikan scanner
    alert(decodedText);
    form.code = decodedText;
    showScanner.value = false;
};
</script>
<template>
    <BarcodeScanner
        v-if="showScanCode"
        @result="onScanResult"
        @close="showScanCode = false"
    />
    <form class="flex flex-col h-full" @submit.prevent="submitForm">
        <div
            class="flex-1 p-4 space-y-5 overflow-y-auto md:p-6 md:space-y-6 custom-scrollbar"
        >
            <div class="flex flex-col gap-5 md:flex-row md:gap-8">
                <div class="flex justify-center flex-shrink-0 md:justify-start">
                    <div class="flex flex-col items-center w-full">
                        <label
                            for="image-upload"
                            :class="[
                                'relative flex flex-col items-center justify-center w-32 h-32 md:w-40 md:h-40 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition border-2 border-dashed',
                                imagePreview
                                    ? 'border-transparent'
                                    : 'border-gray-300 dark:border-gray-500',
                            ]"
                        >
                            <img
                                v-if="imagePreview"
                                :src="imagePreview"
                                class="object-cover w-full h-full rounded-xl"
                                alt="Preview"
                            />
                            <div
                                v-else
                                class="flex flex-col items-center justify-center px-2 pt-5 pb-6 text-center"
                            >
                                <svg
                                    class="w-8 h-8 mb-2 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    ></path>
                                </svg>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-300"
                                >
                                    Upload Foto
                                </p>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    Max 1MB
                                </p>
                            </div>
                            <input
                                id="image-upload"
                                type="file"
                                class="hidden"
                                @change="handleFileChange"
                            />
                        </label>
                        <p
                            v-if="form.errors.image"
                            class="mt-1 text-xs text-center text-red-500"
                        >
                            {{ form.errors.image }}
                        </p>
                    </div>
                </div>

                <div class="flex-1 space-y-4">
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Kode Barang</label
                        >
                        <div class="relative">
                            <TextInput
                                class="w-full pr-10"
                                id="kode"
                                type="text"
                                placeholder="Scan / Input Kode"
                                v-model="form.code"
                            />
                            <button
                                type="button"
                                @click="showScanCode = true"
                                class="absolute right-1 top-1 p-1.5 bg-gray-100 dark:bg-gray-600 rounded-md text-gray-500 dark:text-gray-200 hover:text-lime-600 border border-gray-200 dark:border-gray-500"
                                title="Scan Barcode"
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
                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                                    ></path>
                                </svg>
                            </button>
                        </div>
                        <p
                            v-if="form.errors.code"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Nama Barang
                            <span class="text-red-500">*</span></label
                        >
                        <TextInput
                            class="w-full"
                            id="nama"
                            type="text"
                            placeholder="Nama Produk Lengkap"
                            v-model="form.name"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                <div class="col-span-2 md:col-span-1">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Kategori <span class="text-red-500">*</span></label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.category_id"
                    >
                        <option :value="null">Pilih Kategori</option>
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
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.category_id }}
                    </p>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Tipe</label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.product_type_id"
                    >
                        <option :value="null">Pilih Tipe</option>
                        <option
                            v-for="type in dropdowns.types"
                            :key="type.id"
                            :value="type.id"
                        >
                            {{ type.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.product_type_id"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.product_type_id }}
                    </p>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Satuan</label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.unit_id"
                    >
                        <option :value="null">Pilih Satuan</option>
                        <option
                            v-for="unit in dropdowns.units"
                            :key="unit.id"
                            :value="unit.id"
                        >
                            {{ unit.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.unit_id"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.unit_id }}
                    </p>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Ukuran</label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.size_id"
                    >
                        <option :value="null">Pilih Ukuran</option>
                        <option
                            v-for="size in dropdowns.sizes"
                            :key="size.id"
                            :value="size.id"
                        >
                            {{ size.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Merk</label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.brand_id"
                    >
                        <option :value="null">Pilih Merk</option>
                        <option
                            v-for="brand in dropdowns.brands"
                            :key="brand.id"
                            :value="brand.id"
                        >
                            {{ brand.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Supplier</label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.supplier_id"
                    >
                        <option :value="null">Pilih Supplier</option>
                        <option
                            v-for="supp in dropdowns.suppliers"
                            :key="supp.id"
                            :value="supp.id"
                        >
                            {{ supp.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                    >Deskripsi</label
                >
                <textarea
                    v-model="form.description"
                    name="description"
                    id="description"
                    rows="2"
                    placeholder="Deskripsi / Keterangan tambahan barang"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                ></textarea>
                <p
                    v-if="form.errors.description"
                    class="mt-1 text-xs text-red-500"
                >
                    {{ form.errors.description }}
                </p>
            </div>

            <div
                class="p-4 space-y-4 border border-gray-100 bg-gray-50 dark:bg-gray-900/40 rounded-xl dark:border-gray-700"
            >
                <h3
                    class="text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-white"
                >
                    Harga & Stok
                    <p v-if="mode == 'edit'" class="text-[10px] text-red-500">
                        Atur Harga & Stok hanya bisa dilakukan di edit stok &
                        harga
                    </p>
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Harga Beli (HPP)
                            <span class="text-red-500" v-if="mode !== 'edit'"
                                >*</span
                            ></label
                        >
                        <InputRupiah
                            v-model="form.purchase_price"
                            :class="[
                                'w-full !py-2 !pr-3 !pl-9',
                                mode == 'edit'
                                    ? '!bg-gray-300/50 !text-gray-500 dark:!bg-gray-900/50 dark:!text-gray-200'
                                    : 'dark:!bg-gray-700 dark:!text-white',
                            ]"
                            @input="onPurchaseChange"
                            :disabled="mode == 'edit'"
                            id="price_buy"
                            min="0"
                            placeholder="Rp 0"
                        />

                        <p
                            v-if="form.errors.purchase_price"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.purchase_price }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:!text-gray-200"
                            >Harga Jual
                            <span class="text-red-500" v-if="mode !== 'edit'"
                                >*</span
                            ></label
                        >
                        <InputRupiah
                            v-model="form.selling_price"
                            :class="[
                                'w-full !py-2 !pr-3 !pl-9 ',
                                mode == 'edit'
                                    ? '!bg-gray-300/50 dark:!bg-gray-900/50 !text-gray-500 dark:!text-gray-200'
                                    : '!text-lime-600 !dark:text-lime-400 !font-semibold dark:!bg-gray-700 dark:!text-white',
                            ]"
                            id="price_sell"
                            min="0"
                            :disabled="mode == 'edit'"
                            @input="onSellingChange"
                            placeholder="Rp 0"
                        />
                        <p
                            v-if="form.errors.selling_price"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.selling_price }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Target Margin (Rp)</label
                        >
                        <InputRupiah
                            v-model="form.margin_nominal"
                            :class="[
                                'w-full !py-2 text-sm dark:!bg-gray-700 dark:!text-white',
                            ]"
                            id="margin_nominal"
                            @input="onMarginNominalChange"
                        />
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Target Margin (%)</label
                        >
                        <div class="relative">
                            <TextInput
                                v-model="form.target_margin_percent"
                                :class="['w-full py-2 pr-7 text-sm']"
                                id="target_margin_percent"
                                type="text"
                                @input="onMarginPercentChange"
                            />
                            <span
                                class="absolute text-sm text-gray-500 dark:text-white right-2 top-2"
                                >%</span
                            >
                        </div>
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Stok Saat Ini</label
                        >
                        <TextInput
                            v-model="form.stock"
                            id="stock"
                            type="number"
                            :class="[
                                'w-full py-1.5',
                                mode == 'edit'
                                    ? 'bg-gray-300/50 text-gray-500 dark:!bg-gray-900/50 dark:!text-gray-200'
                                    : '',
                            ]"
                            placeholder="0"
                            :disabled="mode == 'edit'"
                        />
                        <p
                            v-if="form.errors.stock"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.stock }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Min. Stok</label
                        >
                        <TextInput
                            v-model="form.min_stock"
                            id="min_stock"
                            type="number"
                            class="w-full py-1.5 text-red-600"
                            placeholder="0"
                            min="1"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Status Produk</label
                    >
                    <div class="flex items-center gap-4">
                        <label
                            v-for="status in dropdowns.productStatuses"
                            :key="status"
                            class="inline-flex items-center cursor-pointer"
                        >
                            <input
                                type="radio"
                                v-model="form.status"
                                :value="status"
                                class="w-4 h-4 border-gray-300 text-lime-600 focus:ring-lime-500 dark:bg-gray-700"
                            />
                            <span
                                class="ml-2 text-sm text-gray-700 capitalize dark:text-gray-300"
                                >{{ status }}</span
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="h-2"></div>
        </div>

        <div
            class="z-10 flex justify-end gap-3 p-4 border-t border-gray-200 md:p-6 dark:border-gray-700"
        >
            <Link :href="route('products.index')" class="w-full md:w-auto">
                <SecondaryButton class="justify-center w-full md:w-auto">
                    Batal
                </SecondaryButton>
            </Link>
            <PrimaryButton
                type="submit"
                :disabled="form.processing"
                class="justify-center w-full md:w-auto dark:bg-gray-900"
            >
                {{ form.processing ? "Menyimpan..." : "Simpan Produk" }}
            </PrimaryButton>
        </div>
    </form>
</template>
