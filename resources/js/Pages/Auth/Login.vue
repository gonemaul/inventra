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
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="block w-full mt-1"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="block w-full mt-1"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-gray-600 ms-2">Remember me</span>
                </label>

                <div
                    class="flex items-center gap-2 p-1 border rounded-md bg-gray-50"
                >
                    <span class="text-sm font-bold text-gray-700 select-none">
                        {{ num1 }} + {{ num2 }} = ?
                    </span>

                    <input
                        type="number"
                        v-model="captchaAnswer"
                        class="w-16 h-8 text-sm text-center border-gray-300 rounded shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="..."
                    />

                    <button
                        type="button"
                        @click="generateCaptcha"
                        class="text-gray-400 hover:text-indigo-600"
                        title="Refresh Captcha"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="w-4 h-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-if="captchaError"
                class="mt-1 text-xs text-right text-red-600"
            >
                Hasil penjumlahan salah, coba lagi.
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
