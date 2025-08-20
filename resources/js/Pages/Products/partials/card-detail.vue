<script>
import { Link } from "@inertiajs/vue3";
import tabsDetail from "./tabs-detail.vue";

export default {
    components: {
        Link,
        tabsDetail,
    },
    data() {
        return {
            tabs: [
                { key: "ringkasan", label: "Ringkasan Penjualan" },
                { key: "analisis", label: "Analisis Pergerakan" },
                { key: "forecast", label: "Forecasting Restock" },
                { key: "riwayat", label: "Riwayat Stock" },
            ],
            showDelete: false,
        };
    },
};
</script>
<template>
    <div class="grid grid-rows-2 space-y-5">
        <div
            class="p-4 bg-gray-200 border-2 shadow-md border-lime-300 rounded-xl sm:p-5 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="grid grid-cols-12 gap-4">
                <!-- Gambar -->
                <div class="col-span-12 sm:col-span-3">
                    <div
                        class="mx-auto overflow-hidden bg-white rounded-md dark:bg-gray-800"
                    >
                        <img
                            src="no-image.png"
                            alt="Produk"
                            class="object-cover w-full h-full"
                        />
                    </div>
                </div>

                <!-- Detail Produk (tengah) -->
                <div
                    class="flex flex-col justify-between col-span-12 sm:col-span-6"
                >
                    <div class="space-y-3">
                        <h1
                            class="text-2xl font-extrabold leading-tight text-gray-900 dark:text-gray-100"
                        >
                            Deltalube Matic
                        </h1>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="px-2 py-1 text-xs font-semibold tracking-wide bg-gray-300 rounded-md dark:bg-gray-700 dark:text-gray-100"
                            >
                                JBD0382873987239
                            </span>
                            <span
                                class="px-2 py-1 text-xs font-semibold text-white bg-green-500 rounded-md"
                            >
                                Active
                            </span>
                        </div>

                        <p
                            class="max-w-[48ch] text-sm leading-snug text-gray-700 dark:text-gray-300"
                        >
                            Oil Mesin Motor Matic 800 ML, SAE 10/30 W, cocok
                            untuk perjalanan harian maupun jauh, penggunaan
                            untuk motor (Vario, Beat, Scoopy dan sebagainya).
                        </p>
                    </div>

                    <hr
                        class="border-t-2 border-gray-300 sm:my-4 lg:my-0 dark:border-gray-700"
                    />

                    <!-- Info Chips 2x2 -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-2">
                        <!-- Kategori -->
                        <div class="flex flex-col">
                            <label
                                class="text-xs font-medium text-gray-600 dark:text-gray-400"
                            >
                                Kategori
                            </label>
                            <span
                                class="inline-block w-8/12 px-3 py-1 mt-1 text-xs font-medium text-gray-800 bg-white rounded-md shadow-sm dark:bg-gray-600 dark:text-gray-100"
                            >
                                Oli Motor
                            </span>
                        </div>

                        <!-- Ukuran -->
                        <div class="flex flex-col">
                            <label
                                class="text-xs font-medium text-gray-600 dark:text-gray-400"
                            >
                                Ukuran
                            </label>
                            <span
                                class="inline-block w-8/12 px-3 py-1 mt-1 text-xs font-medium text-gray-800 bg-white rounded-md shadow-sm dark:bg-gray-600 dark:text-gray-100"
                            >
                                800 ML
                            </span>
                        </div>

                        <!-- Stok -->
                        <div class="flex flex-col">
                            <label
                                class="text-xs font-medium text-gray-600 dark:text-gray-400"
                            >
                                Stok
                            </label>
                            <span
                                class="inline-block w-8/12 px-3 py-1 mt-1 text-xs font-semibold text-gray-800 bg-white rounded-md shadow-sm dark:bg-gray-600 dark:text-gray-100"
                            >
                                24 Botol
                            </span>
                        </div>

                        <!-- Min Stok -->
                        <div class="flex flex-col">
                            <label
                                class="text-xs font-medium text-gray-600 dark:text-gray-400"
                            >
                                Min Stok
                            </label>
                            <span
                                class="inline-block w-8/12 px-3 py-1 mt-1 text-xs font-semibold text-gray-800 bg-white rounded-md shadow-sm dark:bg-gray-600 dark:text-gray-100"
                            >
                                4 Botol
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Panel kanan: actions, badge, KPI, harga -->
                <div
                    class="flex flex-col justify-between col-span-12 gap-2 sm:col-span-3"
                >
                    <!-- Actions -->
                    <div
                        class="flex flex-wrap justify-center w-full gap-2 lg:justify-between"
                    >
                        <button
                            class="flex-auto p-2 text-white bg-indigo-400 rounded hover:bg-indigo-500"
                        >
                            Inactive
                        </button>
                        <Link
                            aria-label="Edit product"
                            class="flex items-center p-2 text-white bg-yellow-400 rounded hover:bg-yellow-500"
                        >
                            <svg
                                class="w-4 h-4 mr-2 sm:w-5 sm:h-5"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path
                                    d="M19.9902 18.9531C20.5471 18.9534 21 19.4124 21 19.9766C21 20.5418 20.5471 20.9998 19.9902 21H14.2793C13.7224 20.9998 13.2695 20.5419 13.2695 19.9766C13.2696 19.4124 13.7224 18.9533 14.2793 18.9531H19.9902ZM12.2412 3.95703C13.1538 2.78531 14.7463 2.67799 16.0303 3.69922L17.5049 4.87109C18.1097 5.34407 18.5134 5.96737 18.6514 6.62305C18.8105 7.34415 18.6403 8.05244 18.1631 8.66504L9.37598 20.0283C8.97274 20.544 8.37869 20.834 7.74219 20.8447L4.24023 20.8877C4.0493 20.8876 3.89009 20.7589 3.84766 20.5762L3.05176 17.125C2.91398 16.4909 3.05194 15.8352 3.45508 15.3301L9.68457 7.26758C9.79068 7.13908 9.98121 7.11856 10.1084 7.21387L12.7295 9.2998C12.8993 9.43955 13.1329 9.51467 13.377 9.48242C13.8969 9.41792 14.2474 8.94469 14.1943 8.43945C14.1625 8.18164 14.0349 7.96685 13.8652 7.80566C13.8122 7.76267 11.3184 5.7627 11.3184 5.7627C11.1593 5.63368 11.1276 5.39743 11.2549 5.2373L12.2412 3.95703Z"
                                    fill="white"
                                />
                            </svg>
                            Edit
                        </Link>
                        <button
                            @click="showDelete = true"
                            aria-label="Delete product"
                            class="flex items-center p-2 text-white bg-red-400 rounded hover:bg-red-500"
                        >
                            <svg
                                class="w-4 h-4 mr-2 sm:w-5 sm:h-5"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path
                                    d="M18.9395 8.69727C19.1385 8.69738 19.3191 8.78402 19.4619 8.93066C19.5952 9.08766 19.663 9.28326 19.6436 9.48926C19.6429 9.56521 19.1099 16.2984 18.8057 19.1338C18.6151 20.8747 17.493 21.9319 15.8096 21.9609C14.5151 21.9899 13.2497 22 12.0039 22C10.6812 22 9.3874 21.9899 8.13184 21.9609C6.50488 21.9218 5.38206 20.8457 5.20117 19.1338C4.88816 16.2881 4.36472 9.56385 4.35449 9.48926C4.34477 9.28326 4.41071 9.08766 4.54492 8.93066C4.67715 8.78375 4.86811 8.69731 5.06836 8.69727H18.9395ZM14.0645 2C14.9485 2 15.7382 2.61708 15.9668 3.49707L16.1309 4.22656C16.2631 4.82145 16.778 5.24302 17.3711 5.24316H20.2871C20.676 5.24316 20.9998 5.56576 21 5.97656V6.35742C20.9998 6.75821 20.676 7.09082 20.2871 7.09082H3.71387C3.32402 7.09082 3.00025 6.75821 3 6.35742V5.97656C3.00021 5.56576 3.324 5.24316 3.71387 5.24316H6.62988C7.22203 5.24301 7.7369 4.82143 7.87012 4.22754L8.02344 3.5459C8.26078 2.61698 9.04181 2 9.93555 2H14.0645Z"
                                    fill="white"
                                />
                            </svg>
                            Delete
                        </button>
                    </div>

                    <!-- Fast Moving -->
                    <div
                        class="flex items-center justify-center px-3 py-1 text-sm font-semibold text-white bg-green-500 rounded"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linejoin="round"
                            />
                        </svg>
                        Fast Moving
                    </div>

                    <!-- KPI kecil (3 buah) -->
                    <!-- <div
                        class="flex flex-wrap gap-2 bg-white rounded-md lg:justify-between sm:justify-end"
                    >
                        <div
                            class="px-2 py-1 text-right text-[11px] text-red-500 leading-tight"
                        >
                            <div class="font-semibold">Naik 2%</div>
                            <div class="font-semibold">Rp 10.200</div>
                        </div>
                        <div class="px-2 py-1 text-right text-[11px] leading-tight">
                            <div class="font-semibold">8%</div>
                            <div class="opacity-70">Rp 10.200</div>
                        </div>
                        <div class="px-2 py-1 text-right text-[11px] leading-tight">
                            <div class="font-semibold">10%</div>
                            <div class="opacity-70">Rp 10.200</div>
                        </div>
                    </div> -->
                    <div
                        class="flex items-center justify-between gap-3 px-3 py-2 bg-white border-2 border-blue-500 rounded-md shadow-sm dark:bg-gray-700"
                    >
                        <div
                            class="text-[11px] font-semibold text-red-600 dark:text-red-400"
                        >
                            <div class="flex items-center gap-1">
                                <svg
                                    class="w-4 h-4"
                                    viewBox="0 0 25 25"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M20.8336 7.29175L14.7439 13.4766C14.6345 13.5878 14.5792 13.6434 14.5303 13.6872C13.7393 14.3952 12.5427 14.3952 11.7517 13.6872C11.7027 13.6434 11.6475 13.5878 11.538 13.4766C11.4286 13.3655 11.3739 13.3099 11.3249 13.2661C10.5339 12.5581 9.3373 12.5581 8.5463 13.2661C8.49733 13.3099 8.44261 13.3655 8.33317 13.4766L4.1665 17.7084M20.8336 7.29175L20.8332 13.5417M20.8336 7.29175H14.5832"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                                Naik 1.5%
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                Rp 1.200
                            </div>
                        </div>
                        <div class="text-right">
                            <div
                                class="mb-0.5 flex text-[11px] text-gray-500 gap-3 dark:text-gray-400"
                            >
                                Margin 8%
                                <span
                                    class="text-gray-400 line-through dark:text-gray-500"
                                    >Rp 78.000</span
                                >
                            </div>
                            <div
                                class="text-xl font-extrabold text-gray-900 dark:text-white"
                            >
                                Rp 80.000
                            </div>
                        </div>
                    </div>

                    <!-- Harga Beli -->
                    <div
                        class="flex items-center justify-between gap-3 px-3 py-2 bg-white border-2 border-red-500 rounded-md shadow-sm dark:bg-gray-700"
                    >
                        <!-- Persentase perubahan & harga kecil -->
                        <div
                            class="text-[11px] font-semibold text-red-600 dark:text-red-400"
                        >
                            <div class="flex items-center gap-1">
                                <svg
                                    class="w-4 h-4"
                                    viewBox="0 0 25 25"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M20.8336 7.29175L14.7439 13.4766C14.6345 13.5878 14.5792 13.6434 14.5303 13.6872C13.7393 14.3952 12.5427 14.3952 11.7517 13.6872C11.7027 13.6434 11.6475 13.5878 11.538 13.4766C11.4286 13.3655 11.3739 13.3099 11.3249 13.2661C10.5339 12.5581 9.3373 12.5581 8.5463 13.2661C8.49733 13.3099 8.44261 13.3655 8.33317 13.4766L4.1665 17.7084M20.8336 7.29175L20.8332 13.5417M20.8336 7.29175H14.5832"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                Naik 1.5%
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                Rp 1.200
                            </div>
                        </div>

                        <!-- Harga utama -->
                        <div class="text-right">
                            <div
                                class="mb-0.5 flex text-[11px] text-gray-500 gap-3 dark:text-gray-400"
                            >
                                Harga Beli
                                <span
                                    class="text-gray-400 line-through dark:text-gray-500"
                                    >Rp 78.000</span
                                >
                            </div>
                            <div
                                class="text-xl font-extrabold text-gray-900 dark:text-white"
                            >
                                Rp 80.000
                            </div>
                        </div>
                    </div>

                    <!-- Harga Jual -->
                    <div
                        class="flex items-center justify-between gap-3 px-3 py-2 bg-white border-2 border-green-500 rounded-md shadow-sm dark:bg-gray-700"
                    >
                        <div
                            class="text-[11px] font-semibold text-green-600 dark:text-green-400"
                        >
                            <div class="flex items-center gap-1">
                                <svg
                                    class="w-4 h-4"
                                    viewBox="0 0 30 30"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M25.0006 21.25L17.6929 13.8281C17.5615 13.6947 17.4953 13.628 17.4365 13.5754C16.4873 12.7258 15.0514 12.7258 14.1022 13.5754C14.0434 13.628 13.9772 13.6947 13.8458 13.8281C13.7145 13.9615 13.6489 14.0282 13.5901 14.0808C12.6409 14.9304 11.205 14.9304 10.2558 14.0808C10.197 14.0282 10.1313 13.9615 10 13.8281L5 8.75M25.0006 21.25L25 13.75M25.0006 21.25H17.5"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                                Turun 1.5%
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                Rp 1.200
                            </div>
                        </div>
                        <div class="text-right">
                            <div
                                class="mb-0.5 flex text-[11px] text-gray-500 gap-3 dark:text-gray-400"
                            >
                                Harga Jual
                                <span
                                    class="text-gray-400 line-through dark:text-gray-500"
                                    >Rp 82.000</span
                                >
                            </div>
                            <div
                                class="text-xl font-extrabold text-gray-900 dark:text-white"
                            >
                                Rp 78.800
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsif khusus mobile: spasi antar blok -->
            <div class="block mt-2 sm:hidden"></div>
        </div>
        <tabsDetail :tabs="tabs" defaultTab="ringkasan">
            <!-- Ringkasan Penjualan -->
            <template #ringkasan>
                <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Ringkasan Penjualan
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Menampilkan total penjualan harian, mingguan, dan
                        bulanan.
                    </p>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div class="p-3 bg-gray-100 rounded dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Hari Ini
                            </p>
                            <p
                                class="text-lg font-bold text-gray-900 dark:text-white"
                            >
                                Rp 2.300.000
                            </p>
                        </div>
                        <div class="p-3 bg-gray-100 rounded dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Minggu Ini
                            </p>
                            <p
                                class="text-lg font-bold text-gray-900 dark:text-white"
                            >
                                Rp 12.500.000
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Analisis Pergerakan -->
            <template #analisis>
                <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Analisis Pergerakan
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Grafik dan pola pergerakan barang berdasarkan waktu.
                    </p>
                    <div
                        class="flex items-center justify-center h-40 mt-3 bg-gray-100 rounded dark:bg-gray-700"
                    >
                        📊 Grafik placeholder
                    </div>
                </div>
            </template>

            <!-- Forecasting Restock -->
            <template #forecast>
                <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Forecasting Restock
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Prediksi kebutuhan restock barang untuk periode
                        mendatang.
                    </p>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div class="p-3 bg-gray-100 rounded dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Produk A
                            </p>
                            <p
                                class="text-lg font-bold text-red-600 dark:text-red-400"
                            >
                                Restock 20 Unit
                            </p>
                        </div>
                        <div class="p-3 bg-gray-100 rounded dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Produk B
                            </p>
                            <p
                                class="text-lg font-bold text-green-600 dark:text-green-400"
                            >
                                Aman
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Riwayat Stock -->
            <template #riwayat>
                <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Riwayat Stock
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Daftar perubahan stok masuk/keluar terbaru.
                    </p>
                    <ul
                        class="mt-3 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <li
                            class="flex justify-between py-2 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <span>+ 50 Unit Produk A</span>
                            <span class="text-gray-500 dark:text-gray-400"
                                >20 Agustus</span
                            >
                        </li>
                        <li
                            class="flex justify-between py-2 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <span>- 10 Unit Produk B</span>
                            <span class="text-gray-500 dark:text-gray-400"
                                >19 Agustus</span
                            >
                        </li>
                    </ul>
                </div>
            </template>
        </tabsDetail>
    </div>
</template>
