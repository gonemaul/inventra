<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Deferred } from "@inertiajs/vue3";
import FormProduct from "./Components/FormProduct.vue";

const props = defineProps({
    dropdowns: Object,
    product: Object, // Bisa null (create) atau berisi data (edit)
});
</script>
<template>
    <Head title="Tambah Barang" />

    <AuthenticatedLayout headerTitle="Tambah Barang">
        <div class="w-full min-h-screen">
            <Deferred data="dropdowns">
                <template #fallback>
                    <div
                        class="flex flex-col h-full bg-white dark:bg-customBg-tableDark animate-pulse"
                    >
                        <div
                            class="flex-1 p-4 space-y-5 overflow-y-auto md:p-6 md:space-y-6 custom-scrollbar"
                        >
                            <div
                                class="flex flex-col gap-5 md:flex-row md:gap-8"
                            >
                                <div
                                    class="flex justify-center flex-shrink-0 md:justify-start"
                                >
                                    <div
                                        class="w-32 h-32 bg-gray-200 md:w-40 md:h-40 dark:bg-gray-700 rounded-xl"
                                    ></div>
                                </div>

                                <div class="flex-1 space-y-4">
                                    <div>
                                        <div
                                            class="w-24 h-4 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <div
                                            class="w-full h-10 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                    </div>
                                    <div>
                                        <div
                                            class="w-32 h-4 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <div
                                            class="w-full h-10 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-100 dark:border-gray-800" />

                            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                                <div
                                    v-for="i in 6"
                                    :key="i"
                                    :class="{
                                        'col-span-2 md:col-span-1': i === 1,
                                    }"
                                >
                                    <div
                                        class="w-20 h-4 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                                    ></div>
                                    <div
                                        class="w-full h-10 bg-gray-200 rounded dark:bg-gray-700"
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="w-24 h-4 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                                ></div>
                                <div
                                    class="w-full h-20 bg-gray-200 rounded dark:bg-gray-700"
                                ></div>
                            </div>

                            <div
                                class="p-4 space-y-4 border border-gray-100 bg-gray-50 dark:bg-gray-700/40 rounded-xl dark:border-gray-700"
                            >
                                <div
                                    class="w-24 h-3 bg-gray-300 rounded dark:bg-gray-600"
                                ></div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div v-for="j in 2" :key="j">
                                        <div
                                            class="h-4 mb-2 bg-gray-200 rounded dark:bg-gray-600 w-28"
                                        ></div>
                                        <div
                                            class="w-full h-10 bg-gray-200 rounded dark:bg-gray-600"
                                        ></div>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-2 gap-4 md:grid-cols-4"
                                >
                                    <div v-for="k in 4" :key="k">
                                        <div
                                            class="w-16 h-3 mb-2 bg-gray-200 rounded dark:bg-gray-600"
                                        ></div>
                                        <div
                                            class="w-full bg-gray-200 rounded h-9 dark:bg-gray-600"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div class="h-2"></div>
                        </div>

                        <div
                            class="z-10 flex justify-end gap-3 p-4 bg-white border-t border-gray-100 md:p-6 dark:border-gray-800 dark:bg-customBg-tableDark"
                        >
                            <div
                                class="w-full h-10 bg-gray-200 rounded-lg md:w-24 dark:bg-gray-700"
                            ></div>
                            <div
                                class="w-full h-10 bg-gray-300 rounded-lg md:w-36 dark:bg-gray-600"
                            ></div>
                        </div>
                    </div>
                </template>
                <FormProduct :dropdowns="props.dropdowns" mode="create" />
            </Deferred>
        </div>
    </AuthenticatedLayout>
</template>
