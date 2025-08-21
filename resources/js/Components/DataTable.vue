<template>
    <div class="w-full">
        <!-- Search -->
        <div class="flex items-center justify-between mb-3">
            <input
                v-model="search"
                @input="handleSearch"
                type="text"
                placeholder="Cari data..."
                class="w-64 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
            />

            <!-- Per page select -->
            <div class="flex items-center space-x-2">
                <span class="text-sm dark:text-gray-300">Tampilkan</span>
                <select
                    v-model="perPage"
                    @change="fetchTableData"
                    class="px-2 py-1 text-sm bg-white border border-gray-300 rounded-md w-14 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                >
                    <option v-for="n in [5, 10, 20, 50]" :key="n" :value="n">
                        {{ n }}
                    </option>
                </select>
                <span class="text-sm dark:text-gray-300">data</span>
            </div>
        </div>

        <!-- Table -->
        <div
            class="overflow-x-auto bg-white border rounded-lg shadow-sm border-lime-500 dark:border-gray-700"
        >
            <table class="w-full border-collapse">
                <thead
                    class="text-gray-800 bg-gray-100 border-b border-lime-500 dark:bg-gray-700"
                >
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-2 text-sm font-semibold text-left border-r border-gray-500 cursor-pointer select-none dark:text-gray-200"
                            :style="{ width: col.width || '200px' }"
                            @click="toggleSort(col.key)"
                        >
                            <div class="flex items-center">
                                {{ col.label }}
                                <span v-if="sortKey === col.key" class="ml-1">
                                    {{ sortOrder === "asc" ? "▲" : "▼" }}
                                </span>
                            </div>
                        </th>
                        <th
                            class="w-32 px-4 py-2 text-sm font-semibold text-center dark:text-gray-200"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in tableData"
                        :key="item.id"
                        class="border-b-2 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-2 text-sm dark:text-gray-200"
                        >
                            {{ item[col.key] }}
                        </td>
                        <td class="px-4 py-2 text-sm text-center">
                            <button
                                @click="$emit('edit', item)"
                                class="px-2 py-1 mr-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600"
                            >
                                Edit
                            </button>
                            <button
                                @click="$emit('delete', item)"
                                class="px-2 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!loading && tableData.length === 0">
                        <td
                            :colspan="columns.length + 1"
                            class="px-4 py-3 text-center text-gray-500 dark:text-gray-400"
                        >
                            Tidak ada data
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-3">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Menampilkan {{ startItem }} - {{ endItem }} dari
                {{ total }} data
            </div>

            <div class="flex space-x-2">
                <button
                    @click="changePage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 disabled:opacity-50"
                >
                    Prev
                </button>
                <span class="px-3 py-1 text-sm dark:text-gray-300">
                    {{ currentPage }} / {{ totalPages }}
                </span>
                <button
                    @click="changePage(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from "vue";

const props = defineProps({
    columns: Array,
    fetchData: Function,
    perPage: { type: Number, default: 5 },
});

const emit = defineEmits(["edit", "delete"]);

const tableData = ref([]);
const total = ref(0);
const search = ref("");
const currentPage = ref(1);
const perPage = ref(props.perPage);
const sortKey = ref(null);
const sortOrder = ref("asc");
const loading = ref(false);

const totalPages = computed(() => Math.ceil(total.value / perPage.value));
const startItem = computed(() =>
    total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
);
const endItem = computed(() =>
    Math.min(currentPage.value * perPage.value, total.value)
);

async function fetchTableData() {
    loading.value = true;
    const res = await props.fetchData({
        search: search.value,
        page: currentPage.value,
        perPage: perPage.value,
        sortKey: sortKey.value,
        sortOrder: sortOrder.value,
    });
    tableData.value = res.data;
    total.value = res.total;
    loading.value = false;
}

function toggleSort(key) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortOrder.value = "asc";
    }
    fetchTableData();
}

function handleSearch() {
    currentPage.value = 1;
    fetchTableData();
}

function changePage(page) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        fetchTableData();
    }
}

onMounted(fetchTableData);
watch([perPage], fetchTableData);
</script>
