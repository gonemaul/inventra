<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useToast } from "vue-toastification";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const toast = useToast();
// State untuk Captcha
const num1 = ref(0);
const num2 = ref(0);
const captchaAnswer = ref("");
const captchaError = ref(false);

// Generate angka acak 1-10
const generateCaptcha = () => {
    num1.value = Math.floor(Math.random() * 10) + 1;
    num2.value = Math.floor(Math.random() * 10) + 1;
    captchaAnswer.value = ""; // Reset input
    captchaError.value = false;
};

onMounted(() => {
    generateCaptcha();
});

const validateCaptcha = () => {
    const sum = num1.value + num2.value;
    if (parseInt(captchaAnswer.value) !== sum) {
        captchaError.value = true;
        generateCaptcha(); // Reset angka biar ga ditebak terus
        return false; // Gagal
    }
    return true; // Berhasil
};

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    // 1. Cek Captcha dulu
    if (!validateCaptcha()) {
        toast.warning("Captcha salah!"); // Atau gunakan notifikasi toast
        return; // Stop, jangan lanjut login
    }
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Log in" />

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

        <div class="relative z-10 w-full max-w-md px-6 mx-auto">
            <div
                class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-white/60 p-8 relative overflow-hidden animate-slide-up"
            >
                <div
                    class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-lime-400 via-green-500 to-lime-400"
                ></div>

                <div class="mb-8 text-center">
                    <img
                        src="/icons/logo.webp"
                        alt="Logo"
                        class="w-20 h-auto mx-auto mb-3 transition-transform drop-shadow-sm hover:scale-105"
                    />

                    <h2
                        class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-lime-600 to-green-700"
                    >
                        Selamat Datang
                    </h2>
                    <p class="text-sm text-gray-500">Masuk untuk melanjutkan</p>
                </div>

                <div
                    v-if="status"
                    class="p-3 mb-4 text-sm font-medium text-center text-green-700 border border-green-100 rounded-xl bg-green-50"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label
                            for="email"
                            class="block mb-1 ml-1 text-sm font-semibold text-gray-700"
                            >Email</label
                        >
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            class="block w-full px-4 py-3 text-sm transition-all border-gray-200 shadow-sm rounded-xl bg-white/50 focus:border-lime-500 focus:ring-lime-500 focus:ring-2"
                            placeholder="nama@email.com"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 ml-1 text-xs text-red-500"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block mb-1 ml-1 text-sm font-semibold text-gray-700"
                            >Password</label
                        >
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            class="block w-full px-4 py-3 text-sm transition-all border-gray-200 shadow-sm rounded-xl bg-white/50 focus:border-lime-500 focus:ring-lime-500 focus:ring-2"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <p
                            v-if="form.errors.password"
                            class="mt-1 ml-1 text-xs text-red-500"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 p-3 border border-gray-200 shadow-inner bg-gradient-to-r from-gray-50 to-lime-50/50 rounded-xl"
                    >
                        <div class="flex items-center gap-2 pl-1">
                            <div
                                class="flex items-center justify-center w-8 h-8 font-bold rounded-lg shadow-sm bg-lime-100 text-lime-700"
                            >
                                {{ num1 }}
                            </div>
                            <span class="font-bold text-gray-400">+</span>
                            <div
                                class="flex items-center justify-center w-8 h-8 font-bold rounded-lg shadow-sm bg-lime-100 text-lime-700"
                            >
                                {{ num2 }}
                            </div>
                            <span class="font-bold text-gray-400">=</span>
                        </div>

                        <div class="relative flex-1">
                            <input
                                type="number"
                                v-model="captchaAnswer"
                                class="w-full h-10 text-sm font-bold text-center bg-white border-gray-200 rounded-lg focus:border-lime-500 focus:ring-lime-500"
                                placeholder="?"
                            />
                        </div>

                        <button
                            type="button"
                            @click="generateCaptcha"
                            class="p-2 text-gray-400 transition-colors rounded-lg hover:text-lime-600 hover:bg-lime-100"
                            title="Refresh Soal"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="w-5 h-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                                />
                            </svg>
                        </button>
                    </div>

                    <div
                        v-if="captchaError"
                        class="text-xs font-medium text-center text-red-500 animate-pulse"
                    >
                        ⚠️ Hasil penjumlahan salah, silakan coba lagi.
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="border-gray-300 rounded shadow-sm text-lime-600 focus:ring-lime-500"
                            />
                            <span class="text-sm text-gray-600 ms-2"
                                >Ingat saya</span
                            >
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-gray-500 transition-colors hover:text-lime-600 hover:underline"
                        >
                            Lupa password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-base font-bold rounded-xl text-white bg-gradient-to-r from-lime-500 to-green-600 hover:from-lime-600 hover:to-green-700 shadow-lg shadow-lime-500/30 overflow-hidden transition-all hover:-translate-y-1 active:scale-[0.98] mt-6"
                        :class="{
                            'opacity-70 cursor-not-allowed': form.processing,
                        }"
                    >
                        <div
                            class="absolute inset-0 z-10 -translate-x-full animate-shimmer-infinite bg-gradient-to-r from-transparent via-white/40 to-transparent"
                            style="skew-x: -20deg"
                        ></div>

                        <span class="relative z-20 flex items-center">
                            <span v-if="!form.processing">Masuk Sekarang</span>
                            <span v-else class="flex items-center">
                                <svg
                                    class="w-4 h-4 mr-2 -ml-1 text-white animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Memproses...
                            </span>
                        </span>
                    </button>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-500">
                            Belum punya akun?
                            <span
                                class="font-bold text-lime-600 cursor-help hover:text-green-700 hover:underline"
                                title="Silakan hubungi admin untuk pembuatan akun"
                            >
                                Hubungi tim administrator
                            </span>
                        </p>
                    </div>
                </form>
            </div>

            <p class="mt-8 text-xs text-center text-gray-400">
                &copy; {{ new Date().getFullYear() }} Inventra. Secure Login.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Copy paste style animasi yang sama dari Welcome.vue agar konsisten */
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

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-slide-up {
    animation: slideUp 0.6s ease-out forwards;
}

@keyframes shimmer {
    100% {
        transform: translateX(200%);
    }
}
.animate-shimmer-infinite {
    animation: shimmer 3s infinite;
}
</style>
