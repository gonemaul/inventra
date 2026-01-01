<script setup>
import { Head, Link, router } from "@inertiajs/vue3";

defineProps({
    auth: Object,
    appName: {
        type: String,
        default: "Inventra",
    },
});

const profile = () => {
    router.get(route("profile.edit"));
};
const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <Head title="Welcome" />

    <div
        class="relative flex flex-col items-center justify-center min-h-screen overflow-hidden font-sans text-gray-800 bg-gray-100"
    >
        <div
            class="absolute top-0 left-0 rounded-full w-96 h-96 bg-lime-400 mix-blend-multiply filter blur-3xl opacity-30 animate-blob"
        ></div>
        <div
            class="absolute top-0 right-0 bg-green-400 rounded-full w-96 h-96 mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"
        ></div>
        <div
            class="absolute bg-yellow-300 rounded-full -bottom-32 left-20 w-96 h-96 mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"
        ></div>

        <div
            class="relative z-10 w-full max-w-md px-6 py-8 mx-auto perspective-1000"
        >
            <div
                class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-white/60 p-6 relative overflow-hidden animate-float"
            >
                <div
                    class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-lime-400 via-green-500 to-lime-400"
                ></div>

                <div
                    class="mt-4 mb-8 text-center animate-slide-up"
                    style="animation-delay: 100ms"
                >
                    <img
                        src="/icons/logo.webp"
                        loading="lazy"
                        alt="Inventra"
                        class="w-32 h-auto mx-auto mb-4 transition-transform duration-300 drop-shadow-md hover:scale-110 ease-spring"
                    />

                    <h1
                        class="text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-br from-lime-600 to-green-800"
                    >
                        {{ appName }}
                    </h1>
                    <p
                        class="mt-1 text-xs font-semibold tracking-wide text-gray-500 uppercase opacity-80"
                    >
                        Sistem Manajemen Terpadu
                    </p>
                </div>

                <div v-if="auth.user" class="space-y-5">
                    <div
                        @click="profile"
                        class="flex items-center justify-between px-3 py-2 border shadow-sm hover:cursor-pointer bg-gradient-to-r from-lime-50 to-white rounded-2xl border-lime-100 animate-slide-up"
                        style="animation-delay: 200ms"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center rounded-full shadow-inner w-9 h-9 bg-lime-200 text-lime-800"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold leading-none text-gray-800"
                                >
                                    {{ auth.user.name }}
                                </h2>
                                <span
                                    class="text-[10px] text-green-600 font-medium bg-green-100 px-1.5 py-0.5 rounded mt-1 inline-block"
                                    >Online</span
                                >
                            </div>
                        </div>
                        <button
                            @click.stop="logout"
                            class="px-3 py-2 text-xs font-semibold text-gray-400 transition-all duration-200 hover:text-red-500 hover:bg-red-50 rounded-xl"
                        >
                            Keluar
                        </button>
                    </div>

                    <div
                        class="animate-slide-up"
                        style="animation-delay: 300ms"
                    >
                        <Link
                            :href="route('sales.pos')"
                            class="group relative flex items-center p-4 bg-gradient-to-r from-lime-500 to-green-600 rounded-3xl shadow-lg shadow-lime-500/40 text-white overflow-hidden transform transition-all hover:-translate-y-1 hover:shadow-lime-500/60 active:scale-[0.98]"
                        >
                            <div
                                class="absolute inset-0 z-10 -translate-x-full animate-shimmer-infinite bg-gradient-to-r from-transparent via-white/30 to-transparent"
                                style="skew-x: -20deg"
                            ></div>

                            <div
                                class="p-3 mr-4 transition-transform duration-300 shadow-inner bg-white/20 rounded-2xl backdrop-blur-sm group-hover:rotate-6"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-8 h-8 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold tracking-tight">
                                    Kasir / POS
                                </h3>
                                <p class="text-xs font-medium text-lime-100">
                                    Buat transaksi baru
                                </p>
                            </div>
                            <div
                                class="bg-white/20 p-1.5 rounded-full group-hover:bg-white group-hover:text-green-600 transition-colors"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </div>
                        </Link>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 animate-slide-up"
                        style="animation-delay: 400ms"
                    >
                        <Link
                            :href="route('dashboard')"
                            class="flex flex-col items-center justify-center p-4 transition-all duration-300 bg-white border-2 border-transparent shadow-sm rounded-3xl hover:border-lime-300 hover:shadow-lg hover:shadow-lime-100 group active:scale-95"
                        >
                            <div
                                class="flex items-center justify-center w-12 h-12 mb-2 transition-all duration-300 bg-lime-50 text-lime-600 rounded-2xl group-hover:bg-lime-500 group-hover:text-white group-hover:-translate-y-1"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-6 h-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="text-sm font-bold text-gray-600 group-hover:text-lime-700"
                                >Dashboard</span
                            >
                        </Link>

                        <Link
                            :href="route('reports.index')"
                            class="flex flex-col items-center justify-center p-4 transition-all duration-300 bg-white border-2 border-transparent shadow-sm rounded-3xl hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100 group active:scale-95"
                        >
                            <div
                                class="flex items-center justify-center w-12 h-12 mb-2 text-blue-600 transition-all duration-300 bg-blue-50 rounded-2xl group-hover:bg-blue-500 group-hover:text-white group-hover:-translate-y-1"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-6 h-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="text-sm font-bold text-gray-600 group-hover:text-blue-700"
                                >Laporan</span
                            >
                        </Link>

                        <Link
                            :href="route('sales.index')"
                            class="col-span-2 flex items-center p-3.5 bg-white border-2 border-transparent rounded-3xl shadow-sm hover:border-orange-300 hover:shadow-md transition-all duration-300 group active:scale-[0.98]"
                        >
                            <div
                                class="flex items-center justify-center w-10 h-10 mr-4 text-orange-500 transition-colors duration-300 bg-orange-50 rounded-xl group-hover:bg-orange-500 group-hover:text-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <span
                                    class="block text-sm font-bold text-gray-700 transition-colors group-hover:text-orange-600"
                                    >Rekap Harian</span
                                >
                                <div
                                    class="w-12 h-1 mt-1 overflow-hidden transition-colors bg-gray-100 rounded-full group-hover:bg-orange-200"
                                >
                                    <div
                                        class="w-2/3 h-full bg-orange-400 rounded-full"
                                    ></div>
                                </div>
                            </div>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4 text-gray-300 transition-transform group-hover:translate-x-1"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </Link>
                    </div>
                </div>

                <div v-else class="py-4 space-y-8">
                    <div
                        class="space-y-3 text-center animate-slide-up"
                        style="animation-delay: 200ms"
                    >
                        <div
                            class="inline-block bg-lime-100 text-lime-700 text-[10px] font-bold px-3 py-1 rounded-full mb-2"
                        >
                            👋 Selamat Datang Kembali
                        </div>
                        <p class="px-4 text-sm leading-relaxed text-gray-500">
                            Akses sistem manajemen inventori modern Anda. Cepat,
                            Tepat, dan Efisien.
                        </p>
                    </div>

                    <div
                        class="animate-slide-up"
                        style="animation-delay: 300ms"
                    >
                        <Link
                            :href="route('login')"
                            class="relative flex justify-center w-full px-4 py-4 overflow-hidden text-base font-bold text-white transition-all border border-transparent shadow-xl group rounded-2xl bg-gradient-to-r from-lime-500 to-green-600 hover:from-lime-600 hover:to-green-700 shadow-lime-500/20 hover:-translate-y-1 active:scale-95"
                        >
                            <div
                                class="absolute inset-0 z-10 -translate-x-full animate-shimmer-infinite bg-gradient-to-r from-transparent via-white/40 to-transparent"
                                style="skew-x: -20deg"
                            ></div>

                            <span
                                class="absolute relative inset-y-0 left-0 z-20 flex items-center pl-4"
                            >
                                <svg
                                    class="w-6 h-6 transition-colors duration-300 text-lime-200 group-hover:text-white group-hover:rotate-12"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                                    />
                                </svg>
                            </span>
                            <span class="relative z-20 ml-2"
                                >Masuk Aplikasi</span
                            >
                        </Link>
                    </div>
                </div>
            </div>

            <p
                class="mt-8 text-xs font-medium text-center text-gray-400 animate-fade-in"
                style="animation-delay: 800ms"
            >
                &copy; {{ new Date().getFullYear() }} {{ appName }}.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* 1. KEYFRAMES UMUM */
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

/* 2. ENTRANCE ANIMATION (Slide Up) */
@keyframes slideUpFade {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 3. SHIMMER EFFECT (Kilatan Cahaya) */
@keyframes shimmer {
    100% {
        transform: translateX(200%);
    }
}

/* 4. FLOATING CARD (Mengambang Halus) */
@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

/* KELAS CSS UTILITIES */
.animate-blob {
    animation: blob 7s infinite alternate ease-in-out;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}

.animate-slide-up {
    opacity: 0; /* Mulai invisible */
    animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Shimmer yang jalan terus menerus (looping) */
.animate-shimmer-infinite {
    animation: shimmer 3s infinite;
}

/* Shimmer hanya saat hover */
.group-hover\:animate-shimmer:hover {
    animation: shimmer 0.8s;
}

/* Easing khusus untuk scale yang lebih kenyal */
.ease-spring {
    transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
}
</style>
