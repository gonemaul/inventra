<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: "",
});

const confirmLogout = () => {
    confirmingLogout.value = true;
    setTimeout(() => passwordInput.value.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route("other-browser-sessions.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;
    form.reset();
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-gray-900">Sesi Browser</h2>
            <p class="mt-1 text-sm text-gray-600">
                Kelola dan log out sesi aktif Anda di browser dan perangkat
                lain.
            </p>
        </header>

        <div class="mt-5 space-y-6">
            <p class="text-sm text-gray-600">
                Jika perlu, Anda dapat keluar dari semua sesi browser lain di
                semua perangkat Anda. Beberapa sesi terbaru Anda tercantum di
                bawah ini.
            </p>

            <div class="space-y-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-lg text-lime-600 bg-lime-100">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="w-6 h-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 17.25v-1.007"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 3h12a3 3 0 013 3v8a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-semibold text-gray-600">
                            Perangkat Ini
                            <span class="ml-1 text-xs font-bold text-green-500"
                                >(Sedang Aktif)</span
                            >
                        </div>
                        <div class="text-xs text-gray-500">
                            Chrome pada Windows
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center mt-5">
                <PrimaryButton
                    @click="confirmLogout"
                    class="border-0 bg-gradient-to-r from-lime-500 to-green-600 hover:from-lime-600 hover:to-green-700"
                >
                    Log Out Sesi Lain
                </PrimaryButton>

                <ActionMessage :on="form.recentlySuccessful" class="ms-3">
                    Berhasil.
                </ActionMessage>
            </div>

            <Modal :show="confirmingLogout" @close="closeModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        Log Out Sesi Browser Lain
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Silakan masukkan password Anda untuk mengonfirmasi bahwa
                        Anda ingin keluar dari sesi browser lain di semua
                        perangkat Anda.
                    </p>

                    <div class="mt-6">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="block w-3/4 mt-1"
                            placeholder="Password"
                            @keyup.enter="logoutOtherBrowserSessions"
                        />

                        <InputError
                            :message="form.errors.password"
                            class="mt-2"
                        />
                    </div>

                    <div class="flex justify-end mt-6">
                        <SecondaryButton @click="closeModal">
                            Cancel
                        </SecondaryButton>

                        <PrimaryButton
                            class="bg-red-600 ms-3 hover:bg-red-700 focus:ring-red-500"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            @click="logoutOtherBrowserSessions"
                        >
                            Log Out Sesi Lain
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </div>
    </section>
</template>
