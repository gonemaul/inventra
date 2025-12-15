<script setup>
import Modal from "@/Components/Modal.vue";
import { useForm } from "@inertiajs/vue3";
import { watch, computed } from "vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: "create",
    },
    data: {
        type: Object,
        default: () => ({}),
    },
    dataType: {
        type: String,
        default: "category",
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
});
const routeConfig = {
    category: {
        create: {
            method: "post",
            url: () => route("api.settings.storeCategory"),
        },
        edit: {
            method: "put",
            url: (id) => route("api.settings.updateCategory", { id: id }),
        },
    },
    type: {
        create: {
            method: "post",
            url: () => route("api.settings.storeType"),
        },
        edit: {
            method: "put",
            url: (id) => route("api.settings.updateType", { id: id }),
        },
    },
    unit: {
        create: {
            method: "post",
            url: () => route("api.settings.storeUnit"),
        },
        edit: {
            method: "put",
            url: (id) => route("api.settings.updateUnit", { id: id }),
        },
    },
    size: {
        create: {
            method: "post",
            url: () => route("api.settings.storeSize"),
        },
        edit: {
            method: "put",
            url: (id) => route("api.settings.updateSize", { id: id }),
        },
    },
    brand: {
        create: {
            method: "post",
            url: () => route("api.settings.storeBrand"),
        },
        edit: {
            method: "put",
            url: (id) => route("api.settings.updateBrand", { id: id }),
        },
    },
    supplier: {
        create: {
            method: "post",
            url: () => route("api.settings.storeSupplier"),
        },
        edit: {
            method: "put",
            url: (id) => route("api.settings.updateSupplier", { id: id }),
        },
    },
};

const toast = useToast();
const emit = defineEmits(["close", "success"]);
const { isActionLoading } = useActionLoading();
const form = useForm({
    id: null,
    // Fields untuk Kategori, Satuan, Ukuran
    code: "",
    name: "", // (Dipakai juga oleh Supplier)
    description: "",
    // Fields untuk Supplier
    phone: "",
    address: "",
    type: "",
    status: "",
    // untuk type produk
    category_id: null,
    // field satuan
    is_decimal: false,
});

watch(
    () => props.data,
    (newData) => {
        form.clearErrors();
        if (newData && props.mode === "edit") {
            form.id = newData.id;
            form.code = newData.code || "";
            form.name = newData.name || "";
            form.description = newData.description || "";
            form.phone = newData.phone || "";
            form.address = newData.address || "";
            form.type = newData.type || "";
            form.status = newData.status || "";
            form.category_id = newData.category_id || null;
            form.is_decimal = newData.is_decimal || false;
            // (Tambahkan field supplier di sini nanti)
        } else {
            form.reset();
        }
    }
);

const modalTitle = computed(() => {
    switch (props.dataType) {
        case "category":
            return props.mode === "create"
                ? "Tambah Kategori"
                : "Edit Kategori";
        case "type":
            return props.mode === "create"
                ? "Tambah Tipe Produk"
                : "Edit Tipe Produk";
        case "unit":
            return props.mode === "create" ? "Tambah Satuan" : "Edit Satuan";
        case "size":
            return props.mode === "create" ? "Tambah Ukuran" : "Edit Ukuran";
        case "brand":
            return props.mode === "create" ? "Tambah Merk" : "Edit Merk";
        case "supplier":
            return props.mode === "create"
                ? "Tambah Supplier"
                : "Edit Supplier";
        default:
            return "";
    }
});

const submitForm = () => {
    const type = props.dataType; // -> 'category'
    const mode = props.mode;
    const config = routeConfig[type]?.[mode];
    if (!config) {
        console.error(`Config rute tidak ditemukan untuk: ${type} - ${mode}`);
        return;
    }
    const method = config.method;
    const url = config.url(form.id);
    isActionLoading.value = true;
    form.submit(method, url, {
        preserveScroll: true,
        onFinish: () => {
            isActionLoading.value = false;
            if (form.wasSuccessful) {
                emit("close");
                emit("success");
                form.reset();
            }
        },
    });
};
</script>
<template>
    <Modal :show="show" @close="$emit('close')" max-width="lg">
        <div
            class="w-full p-6 bg-white shadow-lg maxs-w-md rounded-xl dark:bg-gray-800"
        >
            <div class="flex items-center justify-between pb-3 border-b">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    {{ modalTitle }}
                </h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>
            <form class="mt-4 space-y-4" @submit.prevent="submitForm">
                <div v-if="dataType == 'type'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Category<span class="text-red-500">*</span></label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm border border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.category_id"
                    >
                        <option v-if="props.mode == 'create'" :value="null">
                            Pilih Kategori
                        </option>
                        <option
                            v-for="cat in props.categories"
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
                <div v-if="dataType != 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Kode<span class="text-red-500">*</span></label
                    ><input
                        type="text"
                        placeholder="Masukkan kode"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        v-model="form.code"
                    />
                    <p
                        v-if="form.errors.code"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>
                <div v-if="dataType != 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Nama<span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan nama"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        v-model="form.name"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <div v-if="dataType == 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Nama Supplier<span class="text-red-500">*</span></label
                    >
                    <input
                        type="text"
                        placeholder="Masukkan nama"
                        class="w-full px-3 py-2 text-sm text-gray-800 border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        v-model="form.name"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <div v-if="dataType == 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >No Hp<span class="text-red-500">*</span></label
                    >
                    <input
                        type="text"
                        placeholder="Masukkan No Hp"
                        class="w-full px-3 py-2 text-sm text-gray-800 border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        v-model="form.phone"
                    />
                    <p
                        v-if="form.errors.phone"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.phone }}
                    </p>
                </div>
                <div v-if="dataType == 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Alamat<span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan alamat"
                        class="w-full px-3 py-2 text-sm text-gray-800 border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        v-model="form.address"
                    />
                    <p
                        v-if="form.errors.address"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.address }}
                    </p>
                </div>
                <div v-if="dataType == 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Type<span class="text-red-500">*</span></label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm text-gray-800 border rounded-lg dark:bg-gray-700 dark:text-white"
                        v-model="form.type"
                    >
                        <option value="" v-if="props.mode == 'create'">
                            Pilih Type
                        </option>
                        <option value="type_online">Online</option>
                        <option value="type_offline">Offline</option>
                    </select>
                    <p
                        v-if="form.errors.type"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.type }}
                    </p>
                </div>
                <div v-if="dataType == 'supplier'">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Status<span class="text-red-500">*</span></label
                    >
                    <select
                        class="w-full px-3 py-2 text-sm text-gray-800 border rounded-lg dark:bg-gray-700 dark:text-white"
                        v-model="form.status"
                    >
                        <option value="" v-if="props.mode == 'create'">
                            Pilih Status
                        </option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                    <p
                        v-if="form.errors.status"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.status }}
                    </p>
                </div>
                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Catatan / Deskripsi
                    </label>
                    <textarea
                        type="text"
                        placeholder="Masukkan Catatan / Deskripsi"
                        class="w-full px-3 py-2 text-sm text-gray-800 border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        v-model="form.description"
                    ></textarea>
                    <p
                        v-if="form.errors.description"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>
                <div v-if="dataType == 'unit'">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="form.is_decimal"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 ..."
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Izinkan diecer (ex. liter, meter)
                        </span>
                    </label>
                    <p
                        v-if="form.errors.is_decimal"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.is_decimal }}
                    </p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "Menyimpan..." : "Simpan" }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
