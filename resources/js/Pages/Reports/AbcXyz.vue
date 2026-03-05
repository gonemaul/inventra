<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";

const props = defineProps({
    insights: Array,
    summary: Object,
    lastAnalysis: String,
});

const toast = usePremiumAlert();
const isAnalyzing = ref(false);
const filterMatrix = ref("");
const filterAbc = ref("");
const filterXyz = ref("");

const formatDate = (d) => {
    if (!d) return "Belum pernah";
    return new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "long", year: "numeric", hour: "numeric", minute: "numeric" }).format(new Date(d));
};

// Group by matrix (A-X, A-Y, etc.)
const matrixCounts = computed(() => {
    const map = {};
    for (const i of props.insights) {
        const m = i.payload?.matrix || "Unknown";
        map[m] = (map[m] || 0) + 1;
    }
    return map;
});

const abcCounts = computed(() => {
    const map = { A: 0, B: 0, C: 0 };
    for (const i of props.insights) {
        const c = i.payload?.abc_class;
        if (c && map[c] !== undefined) map[c]++;
    }
    return map;
});

const xyzCounts = computed(() => {
    const map = { X: 0, Y: 0, Z: 0 };
    for (const i of props.insights) {
        const c = i.payload?.xyz_class;
        if (c && map[c] !== undefined) map[c]++;
    }
    return map;
});

const filteredInsights = computed(() => {
    return props.insights.filter((i) => {
        if (filterMatrix.value && i.payload?.matrix !== filterMatrix.value) return false;
        if (filterAbc.value && i.payload?.abc_class !== filterAbc.value) return false;
        if (filterXyz.value && i.payload?.xyz_class !== filterXyz.value) return false;
        return true;
    });
});

const clearFilters = () => {
    filterMatrix.value = "";
    filterAbc.value = "";
    filterXyz.value = "";
};

const matrixCellClick = (abc, xyz) => {
    if (filterMatrix.value === `${abc}-${xyz}`) {
        filterMatrix.value = "";
    } else {
        filterMatrix.value = `${abc}-${xyz}`;
        filterAbc.value = "";
        filterXyz.value = "";
    }
};

const matrixColor = (abc, xyz) => {
    const score = { A: 3, B: 2, C: 1 }[abc] * { X: 3, Y: 2, Z: 1 }[xyz];
    if (score >= 9) return "bg-emerald-100 dark:bg-emerald-900/30 border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-300";
    if (score >= 6) return "bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400";
    if (score >= 4) return "bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400";
    return "bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400";
};

const matrixGuide = {
    "A-X": "⭐ Prioritas utama. Jaga stok ketat, reorder secara rutin.",
    "A-Y": "🎯 Produk bintang tapi fluktuatif. Buffer stok lebih besar.",
    "A-Z": "⚠️ Revenue tinggi tapi unpredictable. Negosiasi MOQ supplier.",
    "B-X": "✅ Aman. Reorder rutin cukup.",
    "B-Y": "📋 Evaluasi per kuartal. Jaga safety stock sedang.",
    "B-Z": "🔍 Cari pola tersembunyi. Pertimbangkan promo rutin.",
    "C-X": "📦 Stok minimal. Cukup display sebagai variasi.",
    "C-Y": "🤔 Evaluasi kelayakan. Apakah worth for space?",
    "C-Z": "❌ Kandidat hapus dari katalog atau promosikan habis.",
};

const abcBadge = (c) => ({ A: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300", B: "bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300", C: "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300" }[c] || "bg-gray-100 text-gray-500");
const xyzBadge = (c) => ({ X: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300", Y: "bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300", Z: "bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300" }[c] || "bg-gray-100 text-gray-500");

const triggerAnalysis = () => {
    isAnalyzing.value = true;
    router.post(route("reports.smart-insights.analyze"), {}, {
        preserveScroll: true,
        onSuccess: () => { toast.success("Analisa Selesai", "Klasifikasi ABC/XYZ diperbarui."); router.reload(); },
        onError: () => toast.error("Gagal", "Terjadi kesalahan."),
        onFinish: () => { isAnalyzing.value = false; },
    });
};
</script>

<template>
    <Head title="Klasifikasi Produk ABC/XYZ" />
    <AuthenticatedLayout headerTitle="Klasifikasi ABC/XYZ">
        <div class="space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-gray-800 dark:text-white">🏷️ Klasifikasi Produk ABC/XYZ</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Klasifikasi berbasis revenue (ABC) dan prediktabilitas permintaan (XYZ). Analisa terakhir: <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatDate(lastAnalysis) }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <Link :href="route('reports.ai-intelligence')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        AI Center
                    </Link>
                    <button @click="triggerAnalysis" :disabled="isAnalyzing"
                        class="flex items-center gap-2 px-5 py-2 text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-md hover:from-blue-700 hover:to-indigo-700 disabled:opacity-70">
                        <svg v-if="isAnalyzing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ isAnalyzing ? 'Mengklasifikasi...' : 'Perbarui Klasifikasi' }}
                    </button>
                </div>
            </div>

            <!-- OVERVIEW STATS -->
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 text-center col-span-1">
                    <p class="text-xs font-bold text-amber-600 uppercase">Kelas A</p>
                    <p class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ abcCounts.A }}</p>
                    <p class="text-xs text-amber-600 mt-1">Revenue Tinggi</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4 text-center col-span-1">
                    <p class="text-xs font-bold text-blue-600 uppercase">Kelas B</p>
                    <p class="text-3xl font-black text-blue-700 dark:text-blue-400">{{ abcCounts.B }}</p>
                    <p class="text-xs text-blue-600 mt-1">Revenue Sedang</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl p-4 text-center col-span-1">
                    <p class="text-xs font-bold text-gray-500 uppercase">Kelas C</p>
                    <p class="text-3xl font-black text-gray-600 dark:text-gray-300">{{ abcCounts.C }}</p>
                    <p class="text-xs text-gray-500 mt-1">Revenue Minor</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-4 text-center col-span-1">
                    <p class="text-xs font-bold text-green-600 uppercase">X — Stabil</p>
                    <p class="text-3xl font-black text-green-700 dark:text-green-400">{{ xyzCounts.X }}</p>
                    <p class="text-xs text-green-600 mt-1">CV &lt; 0.5</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 text-center col-span-1">
                    <p class="text-xs font-bold text-amber-600 uppercase">Y — Fluktuatif</p>
                    <p class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ xyzCounts.Y }}</p>
                    <p class="text-xs text-amber-600 mt-1">CV 0.5–1.0</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4 text-center col-span-1">
                    <p class="text-xs font-bold text-red-600 uppercase">Z — Acak</p>
                    <p class="text-3xl font-black text-red-700 dark:text-red-400">{{ xyzCounts.Z }}</p>
                    <p class="text-xs text-red-600 mt-1">CV &ge; 1.0</p>
                </div>
            </div>

            <!-- INTERACTIVE MATRIX -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-black text-gray-800 dark:text-white">Matriks ABC/XYZ Interaktif</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Klik sel untuk filter produk di bawah</p>
                    </div>
                    <button v-if="filterMatrix" @click="clearFilters"
                        class="text-xs font-bold px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        ✕ Hapus Filter
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr>
                                <th class="p-3 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold w-16"></th>
                                <th v-for="xyz in ['X','Y','Z']" :key="xyz" class="p-3 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-center font-black">
                                    <span :class="['px-2 py-1 rounded-lg', xyzBadge(xyz)]">{{ xyz }}</span>
                                    <div class="text-xs font-normal text-gray-400 mt-0.5">{{ { X: 'Stabil', Y: 'Fluktuatif', Z: 'Tak Terprediksi' }[xyz] }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="abc in ['A','B','C']" :key="abc">
                                <td class="p-3 border border-gray-200 dark:border-gray-600 text-center font-black">
                                    <span :class="['px-2 py-1 rounded-lg', abcBadge(abc)]">{{ abc }}</span>
                                    <div class="text-xs font-normal text-gray-400 mt-0.5">{{ { A: 'Sultan', B: 'Menengah', C: 'Minor' }[abc] }}</div>
                                </td>
                                <td v-for="xyz in ['X','Y','Z']" :key="xyz"
                                    @click="matrixCellClick(abc, xyz)"
                                    :class="['p-3 border border-gray-200 dark:border-gray-600 text-center cursor-pointer transition-all', matrixColor(abc, xyz), filterMatrix === `${abc}-${xyz}` ? 'ring-2 ring-purple-500 ring-offset-1' : 'hover:opacity-80']">
                                    <div class="font-black text-lg">{{ matrixCounts[`${abc}-${xyz}`] || 0 }}</div>
                                    <div class="text-xs font-bold">{{ abc }}-{{ xyz }}</div>
                                    <div class="text-xs mt-1 font-normal opacity-75 hidden md:block leading-tight">{{ matrixGuide[`${abc}-${xyz}`]?.slice(2, 40) }}...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- GUIDE for selected cell -->
                <div v-if="filterMatrix" class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl">
                    <p class="font-bold text-purple-800 dark:text-purple-300 text-sm">Strategi untuk Produk {{ filterMatrix }}</p>
                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">{{ matrixGuide[filterMatrix] }}</p>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="flex flex-wrap gap-3 items-center">
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">Filter Cepat:</span>
                <div class="flex gap-2 flex-wrap">
                    <button @click="clearFilters" :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all', !filterAbc && !filterXyz && !filterMatrix ? 'bg-gray-800 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300']">Semua</button>
                    <button v-for="abc in ['A','B','C']" :key="abc" @click="() => { filterAbc = filterAbc === abc ? '' : abc; filterMatrix = ''; filterXyz = ''; }"
                        :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all', abcBadge(abc), filterAbc === abc ? 'ring-2 ring-gray-500' : '']">
                        Kelas {{ abc }}
                    </button>
                    <button v-for="xyz in ['X','Y','Z']" :key="xyz" @click="() => { filterXyz = filterXyz === xyz ? '' : xyz; filterMatrix = ''; filterAbc = ''; }"
                        :class="['px-3 py-1.5 text-xs font-bold rounded-lg transition-all', xyzBadge(xyz), filterXyz === xyz ? 'ring-2 ring-gray-500' : '']">
                        {{ xyz }} ({{ { X: 'Stabil', Y: 'Fluktuatif', Z: 'Acak' }[xyz] }})
                    </button>
                </div>
            </div>

            <!-- PRODUCT LIST -->
            <div v-if="insights.length === 0" class="text-center py-16 text-gray-400">
                <p class="text-5xl mb-3">🏷️</p>
                <p class="font-bold text-lg">Belum ada produk terklasifikasi.</p>
                <p class="text-sm mt-1">Diperlukan data penjualan minimal 3 bulan. Klik "Perbarui Klasifikasi" untuk mulai.</p>
            </div>
            <div v-else>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">
                    Menampilkan <strong class="text-gray-800 dark:text-white">{{ filteredInsights.length }}</strong> dari {{ insights.length }} produk
                    <span v-if="filterMatrix || filterAbc || filterXyz"> (difilter)</span>
                </p>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="insight in filteredInsights" :key="insight.id"
                        class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ insight.product?.name || insight.title }}</h4>
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    <span :class="['text-xs font-black px-2 py-1 rounded-lg', abcBadge(insight.payload?.abc_class)]">
                                        A: {{ insight.payload?.abc_class }}
                                    </span>
                                    <span :class="['text-xs font-black px-2 py-1 rounded-lg', xyzBadge(insight.payload?.xyz_class)]">
                                        X: {{ insight.payload?.xyz_class }}
                                    </span>
                                    <span class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded-lg font-bold">
                                        {{ insight.payload?.matrix }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="insight.payload?.revenue_share !== undefined">📊 Revenue: <strong>{{ insight.payload.revenue_share }}%</strong></span>
                                    <span v-if="insight.payload?.cv !== undefined">📉 CV: <strong>{{ insight.payload.cv }}</strong></span>
                                </div>
                                <p v-if="insight.payload?.recommendation" class="text-xs text-gray-500 dark:text-gray-400 mt-2 italic">
                                    💬 {{ insight.payload.recommendation }}
                                </p>
                            </div>
                            <Link v-if="insight.product" :href="`/products/${insight.product.slug}`"
                                class="text-xs text-blue-600 hover:underline font-semibold flex-shrink-0">
                                Detail →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
