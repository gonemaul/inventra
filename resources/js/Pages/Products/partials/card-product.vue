<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    data: Object, // Berisi data produk
    isTrashView: Boolean,
});
defineEmits(["delete", "forceDelete", "restore", "imageClick"]);
</script>
<template>
    <div
        class="flex items-start justify-between w-full gap-4 p-3 mx-auto bg-gray-100 border-2 rounded-lg shadow-md dark:bg-customBg-tableDark bg-opacity-35 border-lime-500 dark:border-gray-500 sm:items-center"
    >
        <!-- Gambar + Status -->
        <div class="flex flex-col justify-center border-2 rounded-md shadow-md">
            <img
                alt="Produk"
                class="object-cover w-32 cursor-pointer lg:w-40 aspect-square rounded-t-md"
                :src="
                    data.image_path
                        ? 'storage/' + data.image_path
                        : 'no-image.png'
                "
                @click="
                    $emit('imageClick', {
                        path: data.image_path,
                        name: data.name,
                    })
                "
            />
            <div
                :class="[
                    'w-32 py-1 text-xs font-medium text-center text-white select-none lg:w-40 rounded-b-md',
                    data.status == 'active' ? 'bg-lime-500' : 'bg-gray-400',
                ]"
            >
                {{ data.status == "active" ? "Aktif" : "Tidak Aktif" }}
            </div>
        </div>

        <div class="flex flex-col w-full gap-2 flex-r lg:flex-row">
            <!-- Info Produk -->
            <div class="flex-1 space-y-3">
                <h2 class="text-lg font-bold leading-tight sm:text-xl">
                    {{ data.full_name }}
                </h2>
                <p
                    class="hidden text-sm text-black dark:text-gray-100 lg:flex sm:text-base"
                >
                    {{ data.description }}
                </p>
                <div class="flex flex-wrap justify-start w-full gap-2">
                    <span
                        class="px-2 py-1 text-[11px] font-medium text-black rounded-xl select-none opacity-80 bg-lime-200"
                    >
                        {{ data.brand?.name }}
                    </span>
                    <span
                        class="px-2 py-1 text-[11px] font-medium text-black rounded-xl select-none opacity-80 bg-lime-200"
                    >
                        {{ data.category?.name }} (
                        {{ data.product_type?.name }} )
                    </span>
                    <span
                        class="px-2 py-1 text-[11px] font-medium text-black rounded-xl select-none bg-lime-200 opacity-80"
                    >
                        {{ data.size?.name }}
                    </span>
                    <span
                        class="px-2 py-1 text-[11px] font-medium text-black rounded-xl select-none bg-lime-200 opacity-80"
                    >
                        {{ data.stock }} {{ data.unit?.name }}
                    </span>
                </div>
            </div>

            <!-- Harga & Aksi -->
            <div
                class="flex flex-col items-end justify-between w-full gap-3 sm:w-auto sm:items-end"
            >
                <!-- Persentase -->
                <div
                    class="items-center hidden gap-1 px-3 py-1 text-xs font-semibold text-red-600 bg-red-200 rounded-full select-none lg:flex"
                >
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M20.0005 7L14.1543 12.9375C14.0492 13.0442 13.9962 13.0976 13.9492 13.1396C13.1899 13.8193 12.0411 13.8193 11.2818 13.1396C11.2347 13.0976 11.1817 13.0442 11.0767 12.9375C10.9716 12.8308 10.9191 12.7774 10.8721 12.7354C10.1127 12.0557 8.96397 12.0557 8.20461 12.7354C8.15759 12.7774 8.10506 12.8308 8 12.9375L4 17M20.0005 7L20 13M20.0005 7H14"
                            stroke="#FF0000"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    {{ data.market_insight.price.trend }}
                    {{ data.market_insight.price.percent }}
                </div>

                <!-- Harga -->
                <div class="text-left sm:text-right">
                    <p
                        class="mb-0 text-xs text-black line-through lg:text-sm dark:text-gray-100 opacity-60"
                    >
                        {{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(data.purchase_price)
                        }}
                    </p>
                    <p
                        class="text-lg font-extrabold leading-none text-black dark:text-gray-100 sm:text-2xl"
                    >
                        {{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(data.selling_price)
                        }}
                    </p>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex justify-center gap-2 lg:w-auto">
                    <Link
                        v-if="!isTrashView"
                        :href="route('products.show', { id: data.id })"
                        aria-label="View product"
                        title="Lihat Produk"
                        class="p-2 text-white bg-blue-500 rounded hover:bg-blue-600"
                    >
                        <!-- Ikon -->
                        <svg
                            class="w-5 h-5 sm:w-6 sm:h-6"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M12 9.75C10.7574 9.75 9.75 10.7574 9.75 12C9.75 13.2426 10.7574 14.25 12 14.25C13.2426 14.25 14.25 13.2426 14.25 12C14.25 10.7574 13.2426 9.75 12 9.75Z"
                                fill="white"
                            />
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M12 5.5C9.38223 5.5 7.02801 6.55139 5.33162 7.85335C4.48232 8.50519 3.78544 9.22913 3.29649 9.93368C2.81686 10.6248 2.5 11.3515 2.5 12C2.5 12.6485 2.81686 13.3752 3.29649 14.0663C3.78544 14.7709 4.48232 15.4948 5.33162 16.1466C7.02801 17.4486 9.38223 18.5 12 18.5C14.6178 18.5 16.972 17.4486 18.6684 16.1466C19.5177 15.4948 20.2146 14.7709 20.7035 14.0663C21.1831 13.3752 21.5 12.6485 21.5 12C21.5 11.3515 21.1831 10.6248 20.7035 9.93368C20.2146 9.22913 19.5177 8.50519 18.6684 7.85335C16.972 6.55139 14.6178 5.5 12 5.5ZM8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12Z"
                                fill="white"
                            />
                        </svg>
                    </Link>
                    <Link
                        v-if="!isTrashView"
                        :href="route('products.edit', { id: data.id })"
                        aria-label="Edit product"
                        title="Edit Produk"
                        class="p-2 text-white bg-yellow-400 rounded hover:bg-yellow-500"
                    >
                        <svg
                            class="w-5 h-5 sm:w-6 sm:h-6"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M19.9902 18.9531C20.5471 18.9534 21 19.4124 21 19.9766C21 20.5418 20.5471 20.9998 19.9902 21H14.2793C13.7224 20.9998 13.2695 20.5419 13.2695 19.9766C13.2696 19.4124 13.7224 18.9533 14.2793 18.9531H19.9902ZM12.2412 3.95703C13.1538 2.78531 14.7463 2.67799 16.0303 3.69922L17.5049 4.87109C18.1097 5.34407 18.5134 5.96737 18.6514 6.62305C18.8105 7.34415 18.6403 8.05244 18.1631 8.66504L9.37598 20.0283C8.97274 20.544 8.37869 20.834 7.74219 20.8447L4.24023 20.8877C4.0493 20.8876 3.89009 20.7589 3.84766 20.5762L3.05176 17.125C2.91398 16.4909 3.05194 15.8352 3.45508 15.3301L9.68457 7.26758C9.79068 7.13908 9.98121 7.11856 10.1084 7.21387L12.7295 9.2998C12.8993 9.43955 13.1329 9.51467 13.377 9.48242C13.8969 9.41792 14.2474 8.94469 14.1943 8.43945C14.1625 8.18164 14.0349 7.96685 13.8652 7.80566C13.8122 7.76267 11.3184 5.7627 11.3184 5.7627C11.1593 5.63368 11.1276 5.39743 11.2549 5.2373L12.2412 3.95703Z"
                                fill="white"
                            />
                        </svg>
                    </Link>
                    <button
                        v-else
                        @click="$emit('restore')"
                        aria-label="Restore product"
                        title="Pulihkan produk"
                        class="p-2 text-white bg-green-400 rounded hover:bg-green-500"
                    >
                        <svg
                            class="w-3 h-3 sm:w-5 sm:h-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M9.11008 5.08C9.98008 4.82 10.9401 4.65 12.0001 4.65C16.7901 4.65 20.6701 8.53 20.6701 13.32C20.6701 18.11 16.7901 21.99 12.0001 21.99C7.21008 21.99 3.33008 18.11 3.33008 13.32C3.33008 11.54 3.87008 9.88 4.79008 8.5"
                                stroke="white"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M7.87012 5.32L10.7601 2"
                                stroke="white"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M7.87012 5.32L11.2401 7.78"
                                stroke="white"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                    <button
                        @click="
                            isTrashView ? $emit('forceDelete') : $emit('delete')
                        "
                        :title="
                            isTrashView
                                ? 'Hapus Permanen Produk'
                                : 'Hapus Produk'
                        "
                        aria-label="Delete product"
                        class="p-2 text-white bg-red-400 rounded hover:bg-red-500"
                    >
                        <svg
                            class="w-5 h-5 sm:w-6 sm:h-6"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M18.9395 8.69727C19.1385 8.69738 19.3191 8.78402 19.4619 8.93066C19.5952 9.08766 19.663 9.28326 19.6436 9.48926C19.6429 9.56521 19.1099 16.2984 18.8057 19.1338C18.6151 20.8747 17.493 21.9319 15.8096 21.9609C14.5151 21.9899 13.2497 22 12.0039 22C10.6812 22 9.3874 21.9899 8.13184 21.9609C6.50488 21.9218 5.38206 20.8457 5.20117 19.1338C4.88816 16.2881 4.36472 9.56385 4.35449 9.48926C4.34477 9.28326 4.41071 9.08766 4.54492 8.93066C4.67715 8.78375 4.86811 8.69731 5.06836 8.69727H18.9395ZM14.0645 2C14.9485 2 15.7382 2.61708 15.9668 3.49707L16.1309 4.22656C16.2631 4.82145 16.778 5.24302 17.3711 5.24316H20.2871C20.676 5.24316 20.9998 5.56576 21 5.97656V6.35742C20.9998 6.75821 20.676 7.09082 20.2871 7.09082H3.71387C3.32402 7.09082 3.00025 6.75821 3 6.35742V5.97656C3.00021 5.56576 3.324 5.24316 3.71387 5.24316H6.62988C7.22203 5.24301 7.7369 4.82143 7.87012 4.22754L8.02344 3.5459C8.26078 2.61698 9.04181 2 9.93555 2H14.0645Z"
                                fill="white"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
