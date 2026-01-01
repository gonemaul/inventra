<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import LogoutOtherBrowserSessionsForm from "./Partials/LogoutOtherBrowserSessionsForm.vue"; // Component Baru
import { Head } from "@inertiajs/vue3";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    sessions: {
        // Props untuk data sesi (jika ada dari backend)
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div
                class="absolute top-0 left-0 rounded-full w-96 h-96 bg-lime-400 mix-blend-multiply filter blur-3xl opacity-20 animate-blob"
            ></div>
            <div
                class="absolute top-0 right-0 bg-green-400 rounded-full w-96 h-96 mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"
            ></div>
            <div
                class="absolute bg-yellow-300 rounded-full -bottom-32 left-20 w-96 h-96 mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"
            ></div>
        </div>

        <template #header>
            <div class="flex items-center gap-2">
                <div class="p-2 rounded-lg bg-lime-100 text-lime-700">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                </div>
                <h2 class="text-xl font-bold leading-tight text-gray-800">
                    Pengaturan Akun
                </h2>
            </div>
        </template>

        <div class="relative z-10 py-12">
            <div class="mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-6">
                        <div
                            class="p-4 sm:p-8 bg-white/80 backdrop-blur-xl border border-white/50 shadow-xl shadow-lime-500/5 sm:rounded-[2rem] relative overflow-hidden group hover:border-lime-300 transition-colors duration-300"
                        >
                            <div
                                class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-lime-400 to-green-500"
                            ></div>

                            <header class="mb-4">
                                <h2 class="text-lg font-bold text-gray-900">
                                    Informasi Profil
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Perbarui informasi profil akun dan alamat
                                    email Anda.
                                </p>
                            </header>

                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                class="max-w-xl"
                            />
                        </div>

                        <div
                            class="p-4 sm:p-8 bg-white/80 backdrop-blur-xl border border-white/50 shadow-xl shadow-lime-500/5 sm:rounded-[2rem] relative overflow-hidden hover:border-lime-300 transition-colors duration-300"
                        >
                            <div
                                class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-green-400 to-emerald-500"
                            ></div>

                            <header class="mb-4">
                                <h2 class="text-lg font-bold text-gray-900">
                                    Perbarui Password
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Pastikan akun Anda menggunakan password yang
                                    panjang dan acak agar tetap aman.
                                </p>
                            </header>

                            <UpdatePasswordForm class="max-w-xl" />
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div
                            class="p-4 sm:p-8 bg-white/80 backdrop-blur-xl border border-white/50 shadow-xl shadow-lime-500/5 sm:rounded-[2rem] relative overflow-hidden hover:border-lime-300 transition-colors duration-300"
                        >
                            <div
                                class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-yellow-400 to-orange-400"
                            ></div>

                            <LogoutOtherBrowserSessionsForm
                                :sessions="sessions"
                                class="max-w-xl"
                            />
                        </div>

                        <div
                            class="p-4 sm:p-8 bg-white/80 backdrop-blur-xl border border-red-100 shadow-xl shadow-red-500/5 sm:rounded-[2rem] relative overflow-hidden hover:border-red-300 transition-colors duration-300"
                        >
                            <div
                                class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-red-400 to-red-600"
                            ></div>

                            <header class="mb-4">
                                <h2 class="text-lg font-bold text-red-600">
                                    Hapus Akun
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Setelah akun dihapus, semua sumber daya dan
                                    data akan dihapus secara permanen.
                                </p>
                            </header>

                            <DeleteUserForm class="max-w-xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Style Animasi Blob (sama seperti sebelumnya) */
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
.animate-blob {
    animation: blob 10s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
