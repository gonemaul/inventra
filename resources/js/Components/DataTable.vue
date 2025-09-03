<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    data: { type: Array, default: () => [] }, //jika client side
    columns: { type: Array, required: true }, // { key, label, sortable?, width?, class?, slot? }
    perPageOptions: { type: Array, default: () => [5, 10, 25, 50] },
    serverSide: { type: Boolean, default: false },
    endpoint: { type: String, default: "" },
    params: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["row-click", "update:params"]);

// Pagination
const perPage = ref(props.perPageOptions[0]);
const currentPage = ref(1);
const lastPage = ref(1);

// Sorting
const sortKey = ref(null);
const sortOrder = ref("asc");

// Data state
const localData = ref(props.data);
const total = ref(props.serverSide ? 0 : props.data.length);
const loading = ref(false);

const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

// Server side fetch
async function fetchServerData() {
    loading.value = true;
    try {
        const response = await axios.get(props.endpoint, {
            params: {
                ...props.params,
                page: currentPage.value,
                per_page: perPage.value,
                sort: sortKey.value,
                order: sortOrder.value,
            },
        });

        const res = response.data;
        localData.value = res.data || [];
        total.value = res.total || 0;
        perPage.value = res.per_page || perPage.value;
        currentPage.value = res.current_page || 1;
        lastPage.value = res.last_page || 1;
    } catch (e) {
        console.error("Fetch error:", e);
    } finally {
        loading.value = false;
    }
}

// Watch untuk server side (semua perubahan)
watch(
    [() => props.params, currentPage, sortKey, sortOrder],
    async () => {
        if (props.serverSide && props.endpoint) {
            await fetchServerData();
        }
    },
    { deep: true }
);

// Watch khusus untuk perPage → reset ke page 1
watch(perPage, async () => {
    if (props.serverSide && props.endpoint) {
        currentPage.value = 1; // reset page
        await fetchServerData();
    }
});

// Auto load kalau server side
onMounted(() => {
    if (props.serverSide) fetchServerData();
});

// Client-side computed
const filteredData = computed(() => {
    if (props.serverSide) return localData.value;

    let rows = [...props.data];

    if (props.params.search) {
        rows = rows.filter((row) =>
            Object.values(row).some((v) =>
                String(v)
                    .toLowerCase()
                    .includes(props.params.search.toLowerCase())
            )
        );
    }

    Object.keys(props.params).forEach((k) => {
        if (k !== "search" && k !== "sort" && k !== "order") {
            if (props.params[k]) {
                rows = rows.filter(
                    (r) => String(r[k]) === String(props.params[k])
                );
            }
        }
    });

    if (sortKey.value) {
        rows.sort((a, b) => {
            const valA = a[sortKey.value];
            const valB = b[sortKey.value];
            if (valA < valB) return sortOrder.value === "asc" ? -1 : 1;
            if (valA > valB) return sortOrder.value === "asc" ? 1 : -1;
            return 0;
        });
    }

    return rows;
});

const paginatedData = computed(() => {
    if (props.serverSide) return localData.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredData.value.slice(start, start + perPage.value);
});

const totalPages = computed(() =>
    props.serverSide
        ? lastPage.value
        : Math.ceil(total.value / perPage.value) || 1
);

watch([perPage, filteredData], () => {
    if (!props.serverSide) {
        total.value = filteredData.value.length;
        currentPage.value = 1;
    }
});
</script>

<template>
    <div
        class="w-full p-5 border-2 rounded-md border-lime-300 text-customText-secondaryLight bg-customBg-light dark:text-customText-secondaryDark dark:border-borderc-dark dark:bg-customBg-dark"
    >
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full border-2 rounded-lg table-fixed">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr class="border-b border-gray-400">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            @click="
                                col.sortable
                                    ? ((sortKey = col.key),
                                      (sortOrder =
                                          sortKey === col.key &&
                                          sortOrder === 'asc'
                                              ? 'desc'
                                              : 'asc'))
                                    : null
                            "
                            :style="{ width: col.width || '150px' }"
                            :class="[
                                'px-4 text-sm md:text-base py-2 border-b dark:border-gray-700 text-left select-none',
                                col.sortable ? 'cursor-pointer' : '',
                                col.class,
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                {{ col.label }}
                                <span
                                    v-if="col.sortable"
                                    class="w-4 h-3 lg:w-5 lg:h-4"
                                >
                                    <!-- default -->
                                    <svg
                                        v-if="sortKey !== col.key"
                                        viewBox="0 0 23 18"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M15.5 15.75V2.25M15.5 2.25L12.375 6M15.5 2.25L18.625 6"
                                            stroke="#6B7280"
                                            stroke-opacity="0.5"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M7.5 2.25L7.5 15.75M7.5 15.75L10.625 12M7.5 15.75L4.375 12"
                                            stroke="#6B7280"
                                            stroke-opacity="0.5"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>

                                    <!-- asc -->
                                    <svg
                                        v-else-if="sortOrder === 'asc'"
                                        viewBox="0 0 23 18"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M15.5 15.75V2.25M15.5 2.25L12.375 6M15.5 2.25L18.625 6"
                                            stroke="#84CC16"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M7.5 2.25L7.5 15.75M7.5 15.75L10.625 12M7.5 15.75L4.375 12"
                                            stroke="#6B7280"
                                            stroke-opacity="0.5"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>

                                    <!-- desc -->
                                    <svg
                                        v-else
                                        viewBox="0 0 23 18"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M7.5 2.25L7.5 15.75M7.5 15.75L10.625 12M7.5 15.75L4.375 12"
                                            stroke="#84CC16"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M15.5 15.75L15.5 2.25M15.5 2.25L12.375 6M15.5 2.25L18.625 6"
                                            stroke="#6B7280"
                                            stroke-opacity="0.5"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="!loading"
                        v-for="row in paginatedData"
                        :key="row.id"
                        @click="emit('row-click', row)"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :style="{ width: col.width || '150px' }"
                            :class="[
                                'px-4 py-2 border-b text-sm md:text-base dark:border-gray-700 truncate',
                                col.class,
                            ]"
                        >
                            <slot v-if="col.slot" :name="col.slot" :row="row" />
                            <span v-else>
                                {{
                                    col.rupiah
                                        ? row[col.key] && row[col.key] !== 0
                                            ? formatRupiah(row[col.key])
                                            : "-"
                                        : row[col.key] && row[col.key] !== 0
                                        ? row[col.key]
                                        : "-"
                                }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!loading && paginatedData.length === 0">
                        <td
                            :colspan="columns.length"
                            class="py-4 text-center text-gray-500"
                        >
                            No data available
                        </td>
                    </tr>
                    <tr v-if="loading">
                        <td
                            :colspan="columns.length"
                            class="py-4 text-center text-gray-500 dark:text-gray-300"
                        >
                            Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div
            class="flex flex-col items-center justify-center gap-4 mt-6 text-center sm:flex-row sm:justify-between"
        >
            <!-- Show entries -->
            <div
                class="flex items-center gap-2 text-gray-500 dark:text-gray-300"
            >
                <label class="text-sm lg:text-base">Show</label>
                <select
                    v-model="perPage"
                    class="px-5 py-1 border rounded dark:bg-gray-800 dark:text-white"
                >
                    <option
                        v-for="opt in perPageOptions"
                        :key="opt"
                        :value="opt"
                    >
                        {{ opt }}
                    </option>
                </select>
                <span class="text-sm lg:text-base">entries</span>
            </div>

            <!-- Info -->
            <div class="text-sm text-gray-500 lg:text-base dark:text-gray-300">
                Showing
                {{ total === 0 ? 0 : (currentPage - 1) * perPage + 1 }}
                to
                {{ Math.min(currentPage * perPage, total) }}
                of {{ total }} entries
            </div>

            <!-- Pagination -->
            <div class="flex flex-wrap justify-center gap-2">
                <button
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                    class="px-3 py-1 border border-gray-300 rounded shadow disabled:opacity-50 disabled:hover:bg-gray-300 dark:bg-gray-800 disabled:dark:hover:bg-gray-800 dark:hover:bg-primary-hover hover:bg-customBg-dark hover:text-white hover:bg-opacity-80 dark:border-gray-600"
                >
                    Prev
                </button>
                <button
                    v-for="page in totalPages"
                    :key="page"
                    @click="currentPage = page"
                    :class="[
                        'px-3 py-1 rounded border shadow border-gray-300 dark:bg-gray-800 dark:border-gray-600',
                        page === currentPage
                            ? 'dark:bg-primary-light bg-customBg-tableDark text-white dark:text-customBg-dark'
                            : 'dark:hover:bg-primary-hover hover:bg-customBg-dark hover:text-white hover:bg-opacity-80',
                    ]"
                >
                    {{ page }}
                </button>
                <button
                    @click="currentPage++"
                    :disabled="currentPage === totalPages"
                    class="px-3 py-1 border border-gray-300 rounded shadow disabled:opacity-50 disabled:hover:bg-gray-300 dark:bg-gray-800 disabled:dark:hover:bg-gray-800 dark:hover:bg-primary-hover hover:bg-customBg-dark hover:text-white hover:bg-opacity-80 dark:border-gray-600"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
