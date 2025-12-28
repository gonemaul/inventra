<script setup>
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    product: Object,
});

const emit = defineEmits(["close", "success"]);

const form = useForm({
    id: props.product?.id,
    purchase_price: props.product?.purchase_price || 0,
    selling_price: props.product?.selling_price || 0,
});

// Helper: Format Rupiah
const formatRupiah = (num) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(num || 0);
};

// Computed: Hitung Profit (Selisih)
const profitNominal = computed(() => {
    return (
        (parseInt(form.selling_price) || 0) -
        (parseInt(form.purchase_price) || 0)
    );
});

// Computed: Hitung Margin % (Markup dari HPP)
const profitPercent = computed(() => {
    const buy = parseInt(form.purchase_price) || 0;
    if (buy <= 0) return 0;
    return ((profitNominal.value / buy) * 100).toFixed(1);
});

// Helper: Set Harga Jual berdasarkan target margin %
const setMargin = (percent) => {
    const buy = parseInt(form.purchase_price) || 0;
    if (buy <= 0) return;
    const marginAmount = buy * (percent / 100);
    form.selling_price = Math.ceil(buy + marginAmount); // Pembulatan ke atas
};

const submit = () => {
    form.put(route("products.update-price", form.id), {
        onSuccess: () => emit("success"),
        preserveScroll: true,
    });
};
</script>

<template>
    <form
        @submit.prevent="submit"
        class="flex flex-col h-full bg-white dark:bg-customBg-tableDark"
    >
        <div class="flex-1 p-4 space-y-6 overflow-y-auto custom-scrollbar">
            <div
                class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-gray-700"
            >
                <div
                    class="flex-shrink-0 w-12 h-12 overflow-hidden bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600"
                >
                    <img
                        v-if="product?.image_url"
                        :src="product.image_url"
                        class="object-cover w-full h-full"
                    />
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center text-[10px] text-gray-400"
                    >
                        NO IMG
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span
                            class="text-[10px] font-bold bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-600 dark:text-gray-300"
                            >{{ product?.code }}</span
                        >
                        <span class="text-[10px] text-gray-400">{{
                            product?.category?.name
                        }}</span>
                    </div>
                    <h3
                        class="text-sm font-bold leading-tight text-gray-800 truncate dark:text-white"
                    >
                        {{ product?.name }}
                    </h3>
                </div>
            </div>

            <div class="space-y-5">
                <div
                    class="relative p-3 border border-yellow-100 bg-yellow-50 dark:bg-yellow-900/10 rounded-xl dark:border-yellow-900/30"
                >
                    <label
                        class="block text-[10px] font-bold text-yellow-700 dark:text-yellow-500 uppercase tracking-wider mb-1"
                    >
                        Harga Beli (HPP)
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-0 top-1.5 text-sm font-bold text-yellow-600/50"
                            >Rp</span
                        >
                        <input
                            v-model="form.purchase_price"
                            type="number"
                            placeholder="0"
                            class="w-full p-0 text-xl font-black text-gray-800 placeholder-gray-300 bg-transparent border-none pl-7 dark:text-white focus:ring-0"
                        />
                    </div>
                    <p
                        v-if="form.errors.purchase_price"
                        class="text-[10px] text-red-500 mt-1"
                    >
                        {{ form.errors.purchase_price }}
                    </p>
                </div>

                <div
                    class="relative p-3 border bg-lime-50 dark:bg-lime-900/10 rounded-xl border-lime-100 dark:border-lime-900/30"
                >
                    <label
                        class="block text-[10px] font-bold text-lime-700 dark:text-lime-500 uppercase tracking-wider mb-1"
                    >
                        Harga Jual
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-0 top-1.5 text-sm font-bold text-lime-600/50"
                            >Rp</span
                        >
                        <input
                            v-model="form.selling_price"
                            type="number"
                            placeholder="0"
                            class="w-full p-0 text-xl font-black text-gray-800 placeholder-gray-300 bg-transparent border-none pl-7 dark:text-white focus:ring-0"
                        />
                    </div>
                    <p
                        v-if="form.errors.selling_price"
                        class="text-[10px] text-red-500 mt-1"
                    >
                        {{ form.errors.selling_price }}
                    </p>
                </div>
            </div>

            <div>
                <label
                    class="text-[10px] text-gray-400 font-bold uppercase mb-2 block"
                    >Set Profit Cepat (%)</label
                >
                <div class="flex gap-2 pb-2 overflow-x-auto no-scrollbar">
                    <button
                        v-for="p in [10, 20, 30, 50, 100]"
                        :key="p"
                        type="button"
                        @click="setMargin(p)"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-lime-50 hover:border-lime-200 hover:text-lime-600 transition whitespace-nowrap"
                    >
                        +{{ p }}%
                    </button>
                </div>
            </div>

            <div
                class="flex items-center justify-between p-4 transition-colors duration-300 border rounded-xl"
                :class="
                    profitNominal < 0
                        ? 'bg-red-50 border-red-100 dark:bg-red-900/20 dark:border-red-800'
                        : 'bg-gray-50 border-gray-100 dark:bg-gray-700/50 dark:border-gray-600'
                "
            >
                <div class="flex flex-col">
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider"
                        :class="
                            profitNominal < 0 ? 'text-red-500' : 'text-gray-400'
                        "
                    >
                        {{
                            profitNominal < 0
                                ? "Potensi Rugi"
                                : "Estimasi Profit"
                        }}
                    </span>
                    <span
                        class="text-xl font-black"
                        :class="
                            profitNominal < 0
                                ? 'text-red-600'
                                : 'text-gray-800 dark:text-white'
                        "
                    >
                        {{ formatRupiah(profitNominal) }}
                    </span>
                </div>

                <div class="text-right">
                    <span
                        class="text-[10px] text-gray-400 uppercase font-bold block mb-0.5"
                        >Margin</span
                    >
                    <span
                        class="inline-block px-2 py-1 text-sm font-bold rounded"
                        :class="
                            profitNominal < 0
                                ? 'bg-red-200 text-red-700'
                                : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                        "
                    >
                        {{ profitPercent }}%
                    </span>
                </div>
            </div>
        </div>

        <div
            class="flex gap-3 p-4 bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-customBg-tableDark"
        >
            <button
                type="button"
                @click="$emit('close')"
                class="w-1/3 px-4 py-3 text-sm font-bold text-gray-700 transition bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            >
                Batal
            </button>
            <button
                type="submit"
                :disabled="form.processing"
                class="flex justify-center w-2/3 px-4 py-3 text-sm font-bold text-white transition transform shadow-lg bg-lime-600 rounded-xl hover:bg-lime-700 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <span v-if="form.processing">Menyimpan...</span>
                <span v-else>Update Harga</span>
            </button>
        </div>
    </form>
</template>

<style scoped>
/* Hilangkan spinner input number */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
