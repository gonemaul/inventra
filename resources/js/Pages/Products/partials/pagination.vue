<script setup>
import { Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
    metadata: Object,
    filters: Object,
});
const pageSizeOptions = [10, 25, 50, 100];
const perPage = ref(props.metadata.per_page);
watch(perPage, (newValue) => {
    let allFilters = { ...props.filters };
    allFilters.per_page = newValue;
    allFilters.page = 1;
    router.get(route("products.index"), allFilters, {
        preserveState: true,
        replace: true,
    });
});
</script>
<template>
    <div
        class="flex flex-col items-center justify-between gap-4 mt-6 md:flex-row"
    >
        <!-- Page size -->
        <div class="flex items-center gap-2">
            <label
                for="pageSize"
                class="text-sm text-gray-600 dark:text-gray-200"
                >Tampilkan:</label
            >
            <select
                id="pageSize"
                v-model="perPage"
                class="px-2 py-1 text-sm border-2 border-gray-500 rounded-md w-14 dark:bg-gray-700 dark:text-white"
            >
                <option
                    v-for="size in pageSizeOptions"
                    :key="size"
                    :value="size"
                >
                    {{ size }}
                </option>
            </select>
        </div>
        <!-- Info jumlah data -->
        <div class="text-sm text-gray-600 dark:text-gray-200">
            Menampilkan
            <span class="font-semibold">{{ metadata.from }}</span> -
            <span class="font-semibold">{{ metadata.to }}</span> dari
            <span class="font-semibold">{{ metadata.total }}</span> data
        </div>

        <!-- Pagination -->
        <div class="flex items-center gap-2">
            <div v-for="(link, key) in metadata.links" :key="key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1 text-sm rounded-md"
                    :class="{
                        'bg-lime-600 text-white': link.active,
                        'bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-lime-300 dark:hover:bg-lime-700':
                            !link.active,
                    }"
                    v-html="link.label"
                ></Link>
                <span
                    v-else
                    class="px-3 py-1 text-sm text-gray-400 bg-gray-200 rounded-md cursor-not-allowed dark:bg-gray-700"
                    v-html="link.label"
                ></span>
            </div>
        </div>
    </div>
</template>
