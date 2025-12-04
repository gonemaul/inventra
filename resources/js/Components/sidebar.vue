<script setup>
import { Link } from "@inertiajs/vue3";
import NavLink from "./NavLink.vue";
import { ref } from "vue";
import { useSidebar } from "@/Composable/useSidebar";
import { useExtended } from "@/Composable/useExtended";
// import { useExtended, toggleExtended } from "@/Composable/useExtended";

const { isSidebarOpen } = useSidebar();
const { isExtended, toggleExtended } = useExtended();
// const isExtended = ref(true); // State untuk melacak kondisi sidebar

// const toggleSidebar = () => {
//     isExpanded.value = !isExpanded.value;
// };

import DashboardIcon from "./Icons/DashboardIcon.vue";
import ProductsIcon from "./Icons/ProductsIcon.vue";
import PurchasesIcon from "./Icons/PurchasesIcon.vue";
import SalesIcon from "./Icons/SalesIcon.vue";
import PaymentsIcon from "./Icons/PaymentsIcon.vue";
import ReportsIcon from "./Icons/ReportsIcon.vue";
import SettingsIcon from "./Icons/SettingsIcon.vue";
const navigation = [
    {
        name: "Dashboard",
        href: route("dashboard"),
        icon: DashboardIcon,
        active: route().current("dashboard"),
    },
    {
        name: "Data Barang",
        href: route("products.index"),
        icon: ProductsIcon,
        active: route().current("products*"),
    },
    {
        name: "Pembelian",
        href: route("purchases.index"),
        icon: PurchasesIcon,
        active: route().current("purchases*"),
    },
    {
        name: "Penjualan",
        href: route("sales.index"),
        icon: SalesIcon,
        active: route().current("sales*"),
    },
    {
        name: "Keuangan",
        href: route("finance.index"),
        icon: PaymentsIcon,
        active: route().current("finance*"),
    },
    {
        name: "Laporan",
        href: route("reports.index"),
        icon: ReportsIcon,
        active: route().current("reports*"),
    },
    {
        name: "Pengaturan",
        href: route("settings"),
        icon: SettingsIcon,
        active: route().current("settings*"),
    },
];
</script>

<template>
    <!-- Sidebar -->
    <aside
        id="sidebar"
        :class="[
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
            isExtended ? 'w-64 p-6' : 'w-20 p-4',
            'fixed top-0 left-0 z-50 flex flex-col justify-between h-full transition-transform duration-200 ease-in-out transform shadow-2xl dark:bg-customBg-tableDark dark:text-customText-dark dark:border-borderc-dark bg-customBg-tableLight text-customText-light border-r border-borderc-light lg:translate-x-0',
        ]"
    >
        <div class="flex items-center mb-6">
            <div
                v-if="isExtended"
                class="flex items-center justify-between gap-5"
            >
                <div class="p-2 bg-white rounded-full">
                    <img src="/images/logo.webp" alt="Logo" class="w-8 h-8" />
                </div>
                <span class="text-xl font-bold">Inventra</span>
            </div>
            <button
                v-if="!isSidebarOpen"
                @click="toggleExtended"
                :class="{ 'ml-auto': isExtended, 'mx-auto': !isExtended }"
                class="transition-colors text-lime-400 hover:text-lime-500"
            >
                <svg
                    :class="{ 'rotate-180': !isExtended }"
                    class="w-10 h-10 text-white rounded-lg bg-lime-400 hover:bg-lime-500"
                    viewBox="0 0 35 35"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M24.7917 23.3334L18.9583 17.5L24.7917 11.6667M16.0417 23.3334L10.2083 17.5L16.0417 11.6667"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>
        </div>
        <!-- Navigation -->
        <nav class="mt-8 mb-auto space-y-6">
            <NavLink
                @click="isSidebarOpen = false"
                v-for="link in navigation"
                :key="link.name"
                :href="link.href"
                :active="link.active"
                :title="link.name"
            >
                <component
                    :is="link.icon"
                    class="flex-shrink-0 w-6 h-6 text-gray-400 group-hover:text-white"
                    :class="{ 'mr-3': isExtended, 'mx-auto': !isExtended }"
                />
                <span v-if="isExtended" class="ml-3">{{ link.name }}</span>
            </NavLink>
        </nav>
        <!-- <div
            class="flex-col hidden py-2 font-medium text-center text-white md:flex"
        >
            <span>Version 1.1.1</span>
            <span>© 2025 GliSentra Group</span>
        </div> -->
        <div
            :class="
                isSidebarOpen
                    ? 'flex justify-between pl-3 text-lg font-medium transition-all duration-200 bg-white border-2 rounded-lg border-lime-500 bg-opacity-80 text-lime-500 lg:hidden'
                    : 'hidden'
            "
        >
            <Link :href="route('profile.edit')" class="flex items-center py-2">
                <svg
                    class="text-lime-500"
                    width="30"
                    height="30"
                    viewBox="0 0 48 48"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M44 24C44 12.98 35.02 4 24 4C12.98 4 4 12.98 4 24C4 29.8 6.5 35.02 10.46 38.68C10.46 38.7 10.46 38.7 10.44 38.72C10.64 38.92 10.88 39.08 11.08 39.26C11.2 39.36 11.3 39.46 11.42 39.54C11.78 39.84 12.18 40.12 12.56 40.4C12.7 40.5 12.82 40.58 12.96 40.68C13.34 40.94 13.74 41.18 14.16 41.4C14.3 41.48 14.46 41.58 14.6 41.66C15 41.88 15.42 42.08 15.86 42.26C16.02 42.34 16.18 42.42 16.34 42.48C16.78 42.66 17.22 42.82 17.66 42.96C17.82 43.02 17.98 43.08 18.14 43.12C18.62 43.26 19.1 43.38 19.58 43.5C19.72 43.54 19.86 43.58 20.02 43.6C20.58 43.72 21.14 43.8 21.72 43.86C21.8 43.86 21.88 43.88 21.96 43.9C22.64 43.96 23.32 44 24 44C24.68 44 25.36 43.96 26.02 43.9C26.1 43.9 26.18 43.88 26.26 43.86C26.84 43.8 27.4 43.72 27.96 43.6C28.1 43.58 28.24 43.52 28.4 43.5C28.88 43.38 29.38 43.28 29.84 43.12C30 43.06 30.16 43 30.32 42.96C30.76 42.8 31.22 42.66 31.64 42.48C31.8 42.42 31.96 42.34 32.12 42.26C32.54 42.08 32.96 41.88 33.38 41.66C33.54 41.58 33.68 41.48 33.82 41.4C34.22 41.16 34.62 40.94 35.02 40.68C35.16 40.6 35.28 40.5 35.42 40.4C35.82 40.12 36.2 39.84 36.56 39.54C36.68 39.44 36.78 39.34 36.9 39.26C37.12 39.08 37.34 38.9 37.54 38.72C37.54 38.7 37.54 38.7 37.52 38.68C41.5 35.02 44 29.8 44 24ZM33.88 33.94C28.46 30.3 19.58 30.3 14.12 33.94C13.24 34.52 12.52 35.2 11.92 35.94C8.88 32.86 7 28.64 7 24C7 14.62 14.62 7 24 7C33.38 7 41 14.62 41 24C41 28.64 39.12 32.86 36.08 35.94C35.5 35.2 34.76 34.52 33.88 33.94Z"
                        fill="currentColor"
                    />
                    <path
                        d="M24 13.8601C19.86 13.8601 16.5 17.2201 16.5 21.3601C16.5 25.4201 19.68 28.7201 23.9 28.8401C23.96 28.8401 24.04 28.8401 24.08 28.8401C24.12 28.8401 24.18 28.8401 24.22 28.8401C24.24 28.8401 24.26 28.8401 24.26 28.8401C28.3 28.7001 31.48 25.4201 31.5 21.3601C31.5 17.2201 28.14 13.8601 24 13.8601Z"
                        fill="currentColor"
                    />
                </svg>

                <span class="ml-3">{{ $page.props.auth.user.name }}</span>
            </Link>
            <Link
                class="flex items-center px-2 bg-lime-500 rounded-tr-md rounded-br-md"
                :href="route('logout')"
                method="POST"
                as="button"
            >
                <svg
                    class="text-white"
                    width="30"
                    height="31"
                    viewBox="0 0 24 25"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M12.75 5.14551C12.75 5.55972 12.4142 5.89551 12 5.89551H5.99999C5.86192 5.89551 5.74999 6.00744 5.74999 6.14551L5.75 18.1455C5.75 18.2836 5.86192 18.3955 6 18.3955L12 18.3955C12.4142 18.3955 12.75 18.7313 12.75 19.1455C12.75 19.5597 12.4142 19.8955 12 19.8955H6C5.0335 19.8955 4.24999 19.112 4.24999 18.1455V6.14551C4.24999 5.17901 5.0335 4.39551 5.99999 4.39551H12C12.4142 4.39551 12.75 4.73129 12.75 5.14551Z"
                        fill="currentColor"
                    />
                    <path
                        d="M8.38839 11.0303C8.38839 10.478 8.83611 10.0303 9.38839 10.0303L14.2444 10.0303C14.2673 9.67487 14.296 9.31972 14.3305 8.965L14.3602 8.6598C14.4084 8.16355 14.9353 7.86637 15.385 8.08184C17.2129 8.95786 18.8677 10.1566 20.2697 11.6205L20.2997 11.6518C20.5668 11.9307 20.5668 12.3704 20.2997 12.6493L20.2697 12.6806C18.8677 14.1445 17.2129 15.3432 15.385 16.2193C14.9353 16.4347 14.4084 16.1376 14.3602 15.6413L14.3305 15.3361C14.296 14.9814 14.2673 14.6262 14.2444 14.2708L9.38839 14.2708C8.83611 14.2708 8.38839 13.8231 8.38839 13.2708L8.38839 11.0303Z"
                        fill="currentColor"
                    />
                </svg>
            </Link>
        </div>
    </aside>
</template>
