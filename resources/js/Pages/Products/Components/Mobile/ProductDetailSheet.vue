<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    data: Object, // Data produk lengkap
});

const emit = defineEmits(["delete", "adjustStock", "adjustPrice"]);
const formatRupiah = (num) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(num);

const monthlyGrowth = computed(() => {
    const current = Number(props.data.qty_this_month) || 0;
    const last = Number(props.data.qty_last_month) || 0;
    const diff = current - last;

    // Hitung Persentase
    let percent = 0;
    if (last > 0) {
        percent = Math.round((diff / last) * 100);
    } else if (current > 0) {
        percent = 100; // Jika bulan lalu 0, bulan ini ada, anggap naik 100%
    }

    return {
        current,
        last,
        diff,
        percent: Math.abs(percent), // Selalu positif untuk tampilan angka
        direction: diff > 0 ? "up" : diff < 0 ? "down" : "neutral",
    };
});
</script>

<template>
    <div class="flex flex-col h-full bg-white dark:bg-gray-800">
        <span
            class="px-1.5 py-0.5 rounded text-center mb-2 text-[10px] font-bold uppercase"
            :class="
                data.status === 'active'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-200 text-gray-600'
            "
        >
            {{ data.status }}
        </span>
        <div class="flex gap-4 border-b border-gray-100 dark:border-gray-700">
            <div
                class="relative flex-shrink-0 w-20 h-20 overflow-hidden bg-gray-100 border rounded-lg dark:bg-gray-700"
            >
                <img
                    v-if="data.image_url"
                    :src="data.image_url"
                    loading="lazy"
                    decoding="async"
                    onerror="this.style.display='none'"
                    onload="this.classList.remove('opacity-0')"
                    class="absolute inset-0 z-10 object-cover w-full h-full opacity-0"
                />
                <div
                    class="absolute inset-0 z-0 flex flex-col items-center justify-center w-full h-full text-gray-300"
                >
                    <svg
                        class="w-8 h-8 mb-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        ></path>
                    </svg>
                    <span class="text-[9px] font-bold">No Image</span>
                </div>
            </div>

            <Link
                class="flex-1 min-w-0"
                :href="route('products.show', data.slug)"
            >
                <div class="flex items-center gap-2 mb-1">
                    <span
                        class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold"
                        >{{ data.code }}</span
                    >
                </div>

                <h2
                    class="mb-1 text-sm font-bold leading-snug text-gray-900 dark:text-white"
                >
                    {{ data.name }}
                </h2>
                <div
                    class="text-lg font-black text-lime-600 dark:text-lime-400"
                >
                    {{ formatRupiah(data.selling_price) }}
                </div>
            </Link>
        </div>

        <div
            class="flex-1 px-2 py-4 space-y-5 overflow-y-auto custom-scrollbar"
        >
            <div class="grid grid-cols-4 gap-2">
                <div
                    class="p-2 text-center border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-700/50 dark:border-gray-700"
                >
                    <div class="text-[10px] text-gray-400 uppercase">Stok</div>
                    <div
                        class="text-lg font-bold text-gray-800 dark:text-white"
                    >
                        {{ data.stock }}
                    </div>
                </div>
                <div
                    class="flex flex-col justify-between col-span-2 p-2 text-center border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-700/50 dark:border-gray-700"
                >
                    <div
                        class="text-[10px] text-gray-400 uppercase font-medium"
                    >
                        Terjual (Total)
                    </div>

                    <div
                        class="my-1 text-lg font-black leading-none text-gray-800 dark:text-white"
                    >
                        {{ data.total_sold_all_time || 0 }}
                    </div>

                    <div
                        class="flex flex-col items-center justify-center gap-1 mt-1"
                    >
                        <div
                            v-if="monthlyGrowth.direction == 'up'"
                            class="flex items-center gap-1.5"
                        >
                            <div
                                class="flex items-center gap-0.5 text-[10px] font-bold text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 px-1.5 py-0.5 rounded-full"
                            >
                                <svg
                                    class="w-3 h-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                    />
                                </svg>
                                <span>+{{ monthlyGrowth.percent }}%</span>
                            </div>
                            <div
                                class="text-[9px] text-gray-500 dark:text-gray-400 font-medium"
                            >
                                {{ data.qty_this_month }}
                                <span class="text-gray-300">vs</span>
                                {{ data.qty_last_month }}
                            </div>
                        </div>

                        <div
                            v-else-if="monthlyGrowth.direction == 'down'"
                            class="flex items-center gap-1.5"
                        >
                            <div
                                class="flex items-center gap-0.5 text-[10px] font-bold text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400 px-1.5 py-0.5 rounded-full"
                            >
                                <svg
                                    class="w-3 h-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"
                                    />
                                </svg>
                                <span>-{{ monthlyGrowth.percent }}%</span>
                            </div>
                            <div
                                class="text-[9px] text-gray-500 dark:text-gray-400 font-medium"
                            >
                                {{ data.qty_this_month }}
                                <span class="text-gray-300">vs</span>
                                {{ data.qty_last_month }}
                            </div>
                        </div>

                        <div
                            v-else-if="data.total_sold_all_time > 0"
                            class="flex flex-col items-center"
                        >
                            <div class="text-[10px] font-medium text-gray-400">
                                Stabil
                                {{
                                    data.qty_this_month > 0
                                        ? "(" + data.qty_this_month + ")"
                                        : ""
                                }}
                            </div>
                            <div
                                v-if="data.qty_last_month > 0"
                                class="text-[9px] text-gray-400"
                            >
                                vs
                                {{ data.qty_last_month }}
                                bulan lalu
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center">
                            <div class="text-[10px] font-medium text-gray-400">
                                Belum terjual
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    :class="[
                        'p-2 text-center border  rounded-lg ',
                        data.is_margin_low
                            ? 'border-red-100 bg-red-50 dark:bg-red-900/20 dark:border-red-800 text-red-600 dark:text-red-400'
                            : 'border-blue-100 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 text-blue-600 dark:text-blue-400',
                    ]"
                >
                    <div class="text-[10px] uppercase">Profit</div>
                    <div class="flex flex-col items-center text-sm font-bold">
                        <span> {{ data.current_margin["percent"] }}% </span>
                        <span class="!text-[10px] mt-1">
                            {{ formatRupiah(data.current_margin["nominal"]) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <div
                    class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <span class="text-gray-500">Kategori</span>
                    <span class="font-medium text-gray-900 dark:text-white"
                        >{{ data.category?.name || "-" }} |
                        {{ data.product_type?.name || "-" }}</span
                    >
                </div>
                <div
                    class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <span class="text-gray-500">Brand</span>
                    <span
                        class="font-medium text-gray-900 uppercase dark:text-white"
                        >{{ data.brand?.name || "-" }}</span
                    >
                </div>
                <div
                    class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <span class="text-gray-500">Ukuran</span>
                    <span class="font-medium text-gray-900 dark:text-white"
                        >{{ data.size?.name || "-" }} /
                        {{ data.unit?.name || "-" }}</span
                    >
                </div>
                <div
                    class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <span class="text-gray-500">Supplier</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{
                        data.supplier?.name
                    }}</span>
                </div>
                <div
                    class="flex justify-between px-2 py-2 -mx-2 border-b border-gray-100 rounded dark:border-gray-700 bg-yellow-50 dark:bg-yellow-900/10"
                >
                    <span class="font-bold text-yellow-700 dark:text-yellow-500"
                        >Harga Beli (HPP)</span
                    >
                    <span class="font-bold text-gray-900 dark:text-white">{{
                        formatRupiah(data.purchase_price)
                    }}</span>
                </div>
            </div>

            <div v-if="data.description">
                <h4 class="mb-1 text-xs font-bold text-gray-500 uppercase">
                    Deskripsi
                </h4>
                <p
                    class="p-3 text-xs leading-relaxed text-gray-600 rounded-lg dark:text-gray-300 bg-gray-50 dark:bg-gray-700/30"
                >
                    {{ data.description }}
                </p>
            </div>
        </div>

        <div
            class="grid grid-cols-2 gap-3 p-4 border-t border-gray-200 dark:border-gray-700"
        >
            <button
                @click="$emit('adjustPrice', data)"
                class="flex items-center justify-center gap-2 py-3 text-sm font-bold text-orange-700 transition bg-orange-100 rounded-xl hover:bg-orange-200"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M5.25 2.25a3 3 0 00-3 3v4.318a3 3 0 00.879 2.121l9.58 9.581c.92.92 2.39 1.186 3.548.428a18.849 18.849 0 005.441-5.44c.758-1.16.492-2.629-.428-3.548l-9.58-9.581a3 3 0 00-2.122-.879H5.25zM6.375 7.5a1.125 1.125 0 100-2.25 1.125 1.125 0 000 2.25z"
                        clip-rule="evenodd"
                    />
                </svg>
                Atur Harga
            </button>
            <button
                @click="$emit('adjustStock', data)"
                class="flex items-center justify-center gap-2 py-3 text-sm font-bold text-blue-700 transition bg-blue-100 rounded-xl hover:bg-blue-200"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z"
                    />

                    <path
                        fill-rule="evenodd"
                        d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z"
                        clip-rule="evenodd"
                    />
                </svg>
                Atur Stok
            </button>

            <Link
                :href="route('products.edit', data.slug)"
                class="flex items-center justify-center gap-2 py-3 text-sm font-bold text-yellow-700 transition bg-yellow-100 rounded-xl hover:bg-yellow-200"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19.9902 18.9531C20.5471 18.9534 21 19.4124 21 19.9766C21 20.5418 20.5471 20.9998 19.9902 21H14.2793C13.7224 20.9998 13.2695 20.5419 13.2695 19.9766C13.2696 19.4124 13.7224 18.9533 14.2793 18.9531H19.9902ZM12.2412 3.95703C13.1538 2.78531 14.7463 2.67799 16.0303 3.69922L17.5049 4.87109C18.1097 5.34407 18.5134 5.96737 18.6514 6.62305C18.8105 7.34415 18.6403 8.05244 18.1631 8.66504L9.37598 20.0283C8.97274 20.544 8.37869 20.834 7.74219 20.8447L4.24023 20.8877C4.0493 20.8876 3.89009 20.7589 3.84766 20.5762L3.05176 17.125C2.91398 16.4909 3.05194 15.8352 3.45508 15.3301L9.68457 7.26758C9.79068 7.13908 9.98121 7.11856 10.1084 7.21387L12.7295 9.2998C12.8993 9.43955 13.1329 9.51467 13.377 9.48242C13.8969 9.41792 14.2474 8.94469 14.1943 8.43945C14.1625 8.18164 14.0349 7.96685 13.8652 7.80566C13.8122 7.76267 11.3184 5.7627 11.3184 5.7627C11.1593 5.63368 11.1276 5.39743 11.2549 5.2373L12.2412 3.95703Z"
                        fill="currentColor"
                    />
                </svg>
                Edit Data
            </Link>
            <button
                @click="$emit('delete', data)"
                class="flex items-center justify-center gap-2 py-3 text-sm font-bold text-red-600 transition bg-white border border-red-200 rounded-xl hover:bg-red-50"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M18.9395 8.69727C19.1385 8.69738 19.3191 8.78402 19.4619 8.93066C19.5952 9.08766 19.663 9.28326 19.6436 9.48926C19.6429 9.56521 19.1099 16.2984 18.8057 19.1338C18.6151 20.8747 17.493 21.9319 15.8096 21.9609C14.5151 21.9899 13.2497 22 12.0039 22C10.6812 22 9.3874 21.9899 8.13184 21.9609C6.50488 21.9218 5.38206 20.8457 5.20117 19.1338C4.88816 16.2881 4.36472 9.56385 4.35449 9.48926C4.34477 9.28326 4.41071 9.08766 4.54492 8.93066C4.67715 8.78375 4.86811 8.69731 5.06836 8.69727H18.9395ZM14.0645 2C14.9485 2 15.7382 2.61708 15.9668 3.49707L16.1309 4.22656C16.2631 4.82145 16.778 5.24302 17.3711 5.24316H20.2871C20.676 5.24316 20.9998 5.56576 21 5.97656V6.35742C20.9998 6.75821 20.676 7.09082 20.2871 7.09082H3.71387C3.32402 7.09082 3.00025 6.75821 3 6.35742V5.97656C3.00021 5.56576 3.324 5.24316 3.71387 5.24316H6.62988C7.22203 5.24301 7.7369 4.82143 7.87012 4.22754L8.02344 3.5459C8.26078 2.61698 9.04181 2 9.93555 2H14.0645Z"
                        fill="currentColor"
                    />
                </svg>
                Hapus
            </button>
        </div>
    </div>
</template>
