<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";

const props = defineProps({
    insights: Array,
    summary: Object,
    filters: Object,
});

const toast = usePremiumAlert();
const actionFilter = ref(props.filters?.action || "");
const isAnalyzing = ref(false);

const formatCurrency = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);

const filteredInsights = computed(() => {
    if (!actionFilter.value) return props.insights;
    return props.insights.filter((i) => i.payload?.action === actionFilter.value);
});

const raiseCount = computed(() => props.insights.filter((i) => i.payload?.action === "raise").length);
const lowerCount = computed(() => props.insights.filter((i) => i.payload?.action === "lower").length);
const totalGain = computed(() => props.insights.reduce((sum, i) => sum + (i.payload?.potential_gain || 0), 0));

const triggerAnalysis = () => {
    isAnalyzing.value = true;
    router.post(route("reports.smart-insights.analyze"), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Analisa Selesai", "Rekomendasi harga telah diperbarui.");
            router.reload();
        },
        onError: () => toast.error("Gagal", "Terjadi kesalahan."),
        onFinish: () => { isAnalyzing.value = false; },
    });
};
</script>

<template>
    <Head title="Smart Pricing AI — Rekomendasi Harga Cerdas" />
    <AuthenticatedLayout headerTitle="Smart Pricing AI">
        <div class="space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-gray-800 dark:text-white">💡 Smart Pricing Assistant</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Rekomendasi harga berbasis AI — analisa margin, tren, dan potensi keuntungan per produk.
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <Link :href="route('reports.ai-intelligence')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        AI Center
                    </Link>
                    <button @click="triggerAnalysis" :disabled="isAnalyzing"
                        class="flex items-center gap-2 px-5 py-2 text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 rounded-lg shadow-md hover:from-emerald-700 hover:to-teal-700 disabled:opacity-70">
                        <svg v-if="isAnalyzing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ isAnalyzing ? 'Menganalisa...' : 'Perbarui Rekomendasi' }}
                    </button>
                </div>
            </div>

            <!-- BAGAIMANA CARA KERJA AI INI -->
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5">
                <h3 class="font-black text-emerald-800 dark:text-emerald-300 mb-3">🤖 Cara Kerja AI Pricing</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-emerald-100 dark:border-emerald-900">
                        <p class="font-bold text-emerald-700 dark:text-emerald-400 mb-2">📈 Kapan AI menyarankan NAIKKAN harga?</p>
                        <ul class="text-gray-600 dark:text-gray-300 space-y-1 text-xs">
                            <li>• Tren penjualan naik &gt;30% bulan ini vs bulan lalu</li>
                            <li>• Margin masih di bawah target yang ditetapkan</li>
                            <li>• Produk tidak dalam kategori dead stock</li>
                            <li>→ <strong class="text-emerald-700">AI sarankan naik 5%</strong> + estimasi gain/bulan</li>
                        </ul>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-amber-100 dark:border-amber-900">
                        <p class="font-bold text-amber-700 dark:text-amber-400 mb-2">📉 Kapan AI menyarankan TURUNKAN harga?</p>
                        <ul class="text-gray-600 dark:text-gray-300 space-y-1 text-xs">
                            <li>• Tidak terjual sama sekali bulan ini (qty = 0)</li>
                            <li>• Margin masih tebal (ada ruang untuk diskon)</li>
                            <li>• Stok masih ada dan tidak nol</li>
                            <li>→ <strong class="text-amber-700">AI sarankan diskon 10%</strong> untuk menggerakkan stok</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-3 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg p-3">
                    <strong>⚠️ Penting:</strong> Rekomendasi ini bersifat saran — bukan keputusan otomatis. Pertimbangkan juga faktor kompetitor, musiman, dan kebijakan harga toko.
                </div>
            </div>

            <!-- SUMMARY STATS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-5 shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">Total Rekomendasi</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white">{{ insights.length }}</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5 shadow-sm text-center">
                    <p class="text-xs font-bold text-emerald-600 uppercase mb-1">📈 Naikkan Harga</p>
                    <p class="text-3xl font-black text-emerald-700 dark:text-emerald-400">{{ raiseCount }}</p>
                    <p class="text-xs text-emerald-600 mt-1">produk</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 shadow-sm text-center">
                    <p class="text-xs font-bold text-amber-600 uppercase mb-1">📉 Turunkan Harga</p>
                    <p class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ lowerCount }}</p>
                    <p class="text-xs text-amber-600 mt-1">produk</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 shadow-sm text-center">
                    <p class="text-xs font-bold text-blue-600 uppercase mb-1">💰 Total Potensi Gain</p>
                    <p class="text-xl font-black text-blue-700 dark:text-blue-400">{{ formatCurrency(totalGain) }}</p>
                    <p class="text-xs text-blue-600 mt-1">per bulan</p>
                </div>
            </div>

            <!-- FILTER -->
            <div class="flex gap-3 items-center">
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">Filter:</span>
                <button @click="actionFilter = ''" :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all', !actionFilter ? 'bg-gray-800 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300']">Semua ({{ insights.length }})</button>
                <button @click="actionFilter = 'raise'" :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all', actionFilter === 'raise' ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400']">📈 Naikkan ({{ raiseCount }})</button>
                <button @click="actionFilter = 'lower'" :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all', actionFilter === 'lower' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">📉 Turunkan ({{ lowerCount }})</button>
            </div>

            <!-- INSIGHT LIST -->
            <div v-if="filteredInsights.length === 0" class="text-center py-16 text-gray-400">
                <p class="text-5xl mb-3">💡</p>
                <p class="font-bold text-lg">Belum ada rekomendasi harga saat ini.</p>
                <p class="text-sm mt-1">Klik "Perbarui Rekomendasi" untuk menjalankan analisa AI.</p>
            </div>
            <div v-else class="grid gap-4 md:grid-cols-2">
                <div v-for="insight in filteredInsights" :key="insight.id"
                    :class="['p-5 border rounded-2xl shadow-sm transition-all hover:shadow-md', insight.payload?.action === 'raise' ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800']">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span :class="['text-sm font-black px-3 py-1 rounded-full', insight.payload?.action === 'raise' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300']">
                                    {{ insight.payload?.action === 'raise' ? '📈 Naikkan Harga' : '📉 Pertimbangkan Diskon' }}
                                </span>
                            </div>
                            <h4 class="font-black text-gray-900 dark:text-white">{{ insight.product?.name || insight.title }}</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ insight.payload?.suggestion }}</p>

                            <!-- PRICE INFO -->
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-3 text-center shadow-sm">
                                    <p class="text-xs text-gray-400 font-bold uppercase">Harga Saat Ini</p>
                                    <p class="text-sm font-black text-gray-800 dark:text-white mt-1">{{ formatCurrency(insight.payload?.current_price) }}</p>
                                </div>
                                <div :class="['rounded-xl p-3 text-center shadow-sm', insight.payload?.action === 'raise' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-amber-100 dark:bg-amber-900/30']">
                                    <p class="text-xs text-gray-500 font-bold uppercase">Harga Usulan</p>
                                    <p :class="['text-sm font-black mt-1', insight.payload?.action === 'raise' ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400']">{{ formatCurrency(insight.payload?.recommended_price) }}</p>
                                </div>
                            </div>

                            <!-- POTENTIAL GAIN -->
                            <div v-if="insight.payload?.potential_gain" class="mt-2 flex items-center gap-2 text-xs">
                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-lg font-bold">
                                    💰 Potensi +{{ formatCurrency(insight.payload.potential_gain) }}/bulan
                                </span>
                                <span v-if="insight.payload?.margin_percent" class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-lg">
                                    Margin sekarang: {{ insight.payload.margin_percent }}%
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex flex-col gap-2">
                            <Link v-if="insight.product" :href="`/products/${insight.product.slug}`"
                                class="text-xs text-blue-600 hover:underline font-semibold whitespace-nowrap">
                                Lihat Produk →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
