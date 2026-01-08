<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
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
console.log(props.sessions);
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-gray-900">Sesi Browser</h2>
            <p class="mt-1 text-sm text-gray-600">
                Lihat daftar perangkat yang sedang login dan kelola keamanan
                sesi Anda.
            </p>
        </header>

        <div class="mt-5 space-y-6">
            <div v-if="sessions.length > 0" class="space-y-4">
                <div
                    v-for="(session, i) in sessions"
                    :key="i"
                    class="flex items-center"
                >
                    <div
                        class="text-lime-600 bg-lime-50 p-2.5 rounded-xl border border-lime-100 shadow-sm"
                    >
                        <svg
                            v-if="session.agent.is_desktop"
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
                        <svg
                            v-else
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
                                d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                            />
                        </svg>
                    </div>

                    <div class="ml-4">
                        <div class="text-sm font-bold text-gray-700">
                            {{
                                session.agent.platform
                                    ? session.agent.platform
                                    : "Unknown OS"
                            }}
                            -
                            {{
                                session.agent.browser
                                    ? session.agent.browser
                                    : "Unknown Browser"
                            }}
                        </div>

                        <div class="text-xs text-gray-500 mt-0.5">
                            {{ session.ip_address }},

                            <span
                                v-if="session.is_current_device"
                                class="ml-1 font-bold text-green-600"
                            >
                                ● Perangkat Ini
                            </span>
                            <span v-else class="text-gray-400">
                                Terakhir aktif {{ session.last_active }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex items-center gap-2 p-4 text-sm text-yellow-700 border border-yellow-100 rounded-lg bg-yellow-50"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span>
                    Tidak ada data sesi. Pastikan
                    <b>SESSION_DRIVER=database</b> di .env
                </span>
            </div>

            <div class="flex items-center pt-4 mt-5 border-t border-gray-100">
                <PrimaryButton
                    @click="confirmLogout"
                    class="border-0 shadow-lg bg-gradient-to-r from-lime-500 to-green-600 hover:from-lime-600 hover:to-green-700 shadow-lime-500/20"
                >
                    Log Out Sesi Lain
                </PrimaryButton>

                <ActionMessage :on="form.recentlySuccessful" class="ms-3">
                    Berhasil log out perangkat lain.
                </ActionMessage>
            </div>

            <Modal :show="confirmingLogout" @close="closeModal">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900">
                        Log Out Sesi Browser Lain
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        Silakan masukkan password Anda untuk mengonfirmasi bahwa
                        Anda ingin keluar dari semua sesi browser lain di
                        seluruh perangkat Anda. Sesi ini akan tetap aktif.
                    </p>

                    <div class="mt-6">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="block w-3/4 mt-1"
                            placeholder="Password Akun"
                            @keyup.enter="logoutOtherBrowserSessions"
                        />

                        <InputError
                            :message="form.errors.password"
                            class="mt-2"
                        />
                    </div>

                    <div class="flex justify-end mt-6">
                        <SecondaryButton @click="closeModal">
                            Batal
                        </SecondaryButton>

                        <PrimaryButton
                            class="bg-red-600 border-transparent ms-3 hover:bg-red-700 focus:ring-red-500"
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
