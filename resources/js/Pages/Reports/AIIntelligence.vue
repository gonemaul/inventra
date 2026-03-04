<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";

const props = defineProps({
    liveStats: Object,
    bundlingPairs: Array,
    abcStats: Object,
    capitalInsights: Array,
    seasonalInsights: Array,
    pricingInsights: Array,
    lastAnalysis: String,
});

const toast = usePremiumAlert();
const isAnalyzing = ref(false);
const activeTab = ref("overview");

const tabs = [
    { id: "overview", label: "Ringkasan", icon: "🧠" },
    { id: "classification", label: "ABC/XYZ", icon: "🏷️" },
    { id: "pricing", label: "Smart Pricing", icon: "💰" },
    { id: "capital", label: "Efisiensi Modal", icon: "📊" },
    { id: "seasonal", label: "Musiman", icon: "📅" },
    { id: "bundling", label: "Bundling", icon: "🔗" },
    { id: "docs", label: "Dokumentasi", icon: "📖" },
];

const formatCurrency = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);

const formatDate = (dateStr) => {
    if (!dateStr) return "Belum pernah dianalisa";
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "numeric",
        minute: "numeric",
    }).format(new Date(dateStr));
};

const totalInsights = computed(() =>
    Object.values(props.liveStats || {}).reduce((a, b) => a + b, 0)
);

const statFor = (type) => props.liveStats?.[type] || 0;

// ABC/XYZ breakdown
const abcBreakdown = computed(() => ({
    A: props.abcStats?.['A'] || 0,
    B: props.abcStats?.['B'] || 0,
    C: props.abcStats?.['C'] || 0,
}));

const severityClass = (severity) => {
    if (severity === 'critical') return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300';
    if (severity === 'warning') return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300';
    return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300';
};

const triggerAnalysis = () => {
    isAnalyzing.value = true;
    router.post(route("reports.smart-insights.analyze"), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Analisa Selesai", "Semua kecerdasan telah diperbarui.");
            router.reload();
        },
        onError: () => toast.error("Gagal", "Terjadi kesalahan."),
        onFinish: () => { isAnalyzing.value = false; },
    });
};

// Intelligence feature docs
const intelligenceFeatures = [
    {
        id: 1,
        name: "Prediksi Kehabisan Stok",
        type: "Predictive Analytics",
        typeColor: "bg-purple-100 text-purple-800",
        file: "InventoryAnalyzer.php",
        inputKeys: ["stock", "sales 7/14/30 hari"],
        outputKeys: ["avg_daily (terbobot)", "days_left", "stockout_date", "status"],
        logic: "Weighted Average: (7d×50%) + (14d×30%) + (30d×20%). Prediksi tanggal habis = stok / velocitas harian.",
        usedIn: ["Smart Insights", "Product List", "Dashboard Widget"],
        icon: "📦",
    },
    {
        id: 2,
        name: "Auto-Adjustment Safety Stock",
        type: "Adaptive Logic",
        typeColor: "bg-indigo-100 text-indigo-800",
        file: "InventoryAnalyzer.php",
        inputKeys: ["velocitas terbobot", "lead_time (7 hari)", "safety_buffer (1.3x)"],
        outputKeys: ["dynamic_min_stock", "is_dynamic_warning"],
        logic: "dynamic_min = ceil(velocity × lead_time × safety_buffer). Warning jika stok ≤ dynamic_min meski di atas min_stock manual.",
        usedIn: ["Smart Insights", "Restock Insight"],
        icon: "🛡️",
    },
    {
        id: 3,
        name: "Smart Pricing Assistant",
        type: "Prescriptive AI",
        typeColor: "bg-emerald-100 text-emerald-800",
        file: "FinancialAnalyzer.php",
        inputKeys: ["margin_percent", "growth_percent", "qty_this_month", "purchase_price"],
        outputKeys: ["action (raise/lower)", "recommended_price", "potential_gain", "suggestion"],
        logic: "Raise: tren naik >30% & margin masih bisa naik → saran +5%. Lower: 0 terjual & margin besar → saran diskon 10%.",
        usedIn: ["Smart Insights (price_recommendation)", "Margin Alert"],
        icon: "💡",
    },
    {
        id: 4,
        name: "ABC/XYZ Classification",
        type: "Statistical Analysis",
        typeColor: "bg-blue-100 text-blue-800",
        file: "ClassificationAnalyzer.php",
        inputKeys: ["revenue 3 bulan", "qty per bulan (12 bulan)"],
        outputKeys: ["abc_class (A/B/C)", "xyz_class (X/Y/Z)", "matrix", "cv", "recommendation"],
        logic: "ABC: revenue share Pareto. XYZ: Coefficient of Variation dari 12 bulan qty. CV<0.5=X, <1.0=Y, ≥1.0=Z.",
        usedIn: ["Smart Insights (abc_xyz_classification)", "Halaman AI Intelligence"],
        icon: "🏷️",
    },
    {
        id: 5,
        name: "Seasonal Restocking Planner",
        type: "Predictive Analytics",
        typeColor: "bg-amber-100 text-amber-800",
        file: "SeasonalRestockAnalyzer.php",
        inputKeys: ["avg_daily_velocity", "histori bulan sama tahun lalu", "lead_time (7 hari)"],
        outputKeys: ["peak_factor", "restock_needed", "buy_deadline_days", "urgency", "estimated_cost"],
        logic: "Bandingkan penjualan bulan depan vs bulan sebelumnya tahun lalu. Jika naik >30% → peak. Buffer = velocity×peak_factor×30hari×1.2.",
        usedIn: ["Smart Insights (seasonal_restock)", "Halaman AI Intelligence"],
        icon: "📅",
    },
    {
        id: 6,
        name: "Capital Efficiency Advisor",
        type: "Financial Analytics",
        typeColor: "bg-red-100 text-red-800",
        file: "CapitalEfficiencyAnalyzer.php",
        inputKeys: ["stock × purchase_price", "qty terjual 90 hari"],
        outputKeys: ["itr", "dsi", "capital_locked", "holding_cost_monthly", "efficiency_score (A-F)"],
        logic: "ITR = (COGS annualized) / (Nilai Stok). DSI = 365/ITR. Holding cost = 1%/bulan dari capital_locked.",
        usedIn: ["Smart Insights (capital_efficiency)", "Halaman AI Intelligence"],
        icon: "📊",
    },
    {
        id: 7,
        name: "Product Bundling (Apriori)",
        type: "Machine Learning",
        typeColor: "bg-pink-100 text-pink-800",
        file: "ProductAssociationAnalyzer.php",
        inputKeys: ["transaksi penjualan 90 hari (sale_items)"],
        outputKeys: ["support", "confidence", "lift", "pair_count", "recommendation"],
        logic: "Market Basket Analysis. Threshold: support≥1%, confidence≥25%, lift≥1.2. Simpan top 20 pasangan terkuat.",
        usedIn: ["Smart Insights (bundling_recommendation)", "Halaman AI Intelligence"],
        icon: "🔗",
    },
    {
        id: 8,
        name: "Dead Stock Detection",
        type: "Rule-Based",
        typeColor: "bg-gray-100 text-gray-700",
        file: "InventoryAnalyzer.php",
        inputKeys: ["stock > 0", "last_sale_date"],
        outputKeys: ["days_inactive", "frozen_asset", "status=dead_stock"],
        logic: "Stok > 0 DAN tidak terjual ≥ 90 hari → dead stock. frozen_asset = stock × purchase_price.",
        usedIn: ["Smart Insights (dead_stock)", "Reports/DeadStock", "Dashboard"],
        icon: "🕸️",
    },
    {
        id: 9,
        name: "Shop Health Score",
        type: "Composite Index",
        typeColor: "bg-teal-100 text-teal-800",
        file: "InsightService.php",
        inputKeys: ["critical_count", "total_products", "overdue_invoices"],
        outputKeys: ["health_score (0-100)"],
        logic: "stock_health = (total - critical) / total × 70. finance_health berdasarkan hutang jatuh tempo × 30.",
        usedIn: ["Dashboard SmartAssistantWidget"],
        icon: "💚",
    },
];
</script>

<template>
    <Head title="AI Intelligence - Dokumentasi Kecerdasan Sistem" />
    <AuthenticatedLayout headerTitle="AI Intelligence Center">
        <div class="space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col gap-4 md:flex-row md:items-start justify-between">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-gray-800 dark:text-white">
                        🧠 AI Intelligence Center
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Dokumentasi lengkap kecerdasan yang tertanam di sistem beserta data live.
                        Analisa terakhir: <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatDate(lastAnalysis) }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <Link :href="route('reports.index')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 transition bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-blue-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali
                    </Link>
                    <Link :href="route('reports.smart-insights')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                        📋 Lihat Smart Insights
                    </Link>
                    <button @click="triggerAnalysis" :disabled="isAnalyzing"
                        class="flex items-center gap-2 px-5 py-2 text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg shadow-md hover:from-purple-700 hover:to-indigo-700 disabled:opacity-70">
                        <svg v-if="isAnalyzing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ isAnalyzing ? 'Menganalisa...' : 'Jalankan Semua Analisa' }}
                    </button>
                </div>
            </div>

            <!-- STAT CARDS OVERVIEW -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                <div class="bg-gradient-to-br from-purple-600 to-indigo-700 text-white p-4 rounded-2xl shadow col-span-2 md:col-span-1 lg:col-span-1">
                    <p class="text-xs font-bold uppercase tracking-wider opacity-80">Total Insight</p>
                    <p class="text-3xl font-black mt-1">{{ totalInsights }}</p>
                    <p class="text-xs opacity-70 mt-1">aktif di sistem</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase">ABC/XYZ</p>
                    <p class="text-2xl font-black text-blue-600">{{ statFor('abc_xyz_classification') }}</p>
                    <p class="text-xs text-gray-400">produk terklasifikasi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase">Pricing AI</p>
                    <p class="text-2xl font-black text-emerald-600">{{ statFor('price_recommendation') }}</p>
                    <p class="text-xs text-gray-400">rekomendasi harga</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase">Modal</p>
                    <p class="text-2xl font-black text-red-600">{{ statFor('capital_efficiency') }}</p>
                    <p class="text-xs text-gray-400">modal kurang efisien</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase">Musiman</p>
                    <p class="text-2xl font-black text-amber-600">{{ statFor('seasonal_restock') }}</p>
                    <p class="text-xs text-gray-400">stok perlu disiapkan</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase">Bundling</p>
                    <p class="text-2xl font-black text-pink-600">{{ statFor('bundling_recommendation') }}</p>
                    <p class="text-xs text-gray-400">pasangan produk</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl shadow-sm text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase">Restock</p>
                    <p class="text-2xl font-black text-orange-600">{{ statFor('restock') }}</p>
                    <p class="text-xs text-gray-400">perlu dibelikan</p>
                </div>
            </div>

            <!-- TABS -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                <div class="flex overflow-x-auto border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        :class="['flex items-center gap-2 px-5 py-3.5 text-sm font-semibold whitespace-nowrap transition-all border-b-2 -mb-px',
                            activeTab === tab.id
                                ? 'border-purple-600 text-purple-700 bg-white dark:bg-gray-800 dark:text-purple-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                        <span>{{ tab.icon }}</span>
                        <span>{{ tab.label }}</span>
                    </button>
                </div>
                <div class="p-6">

                    <!-- TAB: OVERVIEW -->
                    <div v-if="activeTab === 'overview'" class="space-y-4">
                        <h3 class="text-lg font-black text-gray-800 dark:text-white">9 Kecerdasan Tertanam di Sistem</h3>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div v-for="feat in intelligenceFeatures" :key="feat.id"
                                class="p-4 border border-gray-100 dark:border-gray-700 rounded-xl hover:border-purple-300 dark:hover:border-purple-700 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="text-2xl flex-shrink-0">{{ feat.icon }}</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">{{ feat.name }}</h4>
                                            <span :class="['text-xs px-2 py-0.5 rounded-full font-semibold', feat.typeColor]">{{ feat.type }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">📂 {{ feat.file }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-2">{{ feat.logic }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <span v-for="loc in feat.usedIn" :key="loc" class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full">{{ loc }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: ABC/XYZ -->
                    <div v-if="activeTab === 'classification'" class="space-y-5">
                        <div>
                            <h3 class="text-lg font-black text-gray-800 dark:text-white">ABC/XYZ Product Classification</h3>
                            <p class="text-sm text-gray-500 mt-1">Klasifikasi produk berdasarkan kontribusi revenue (ABC) dan prediktabilitas permintaan (XYZ).</p>
                        </div>
                        <!-- ABC Stats -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                                <p class="text-xs font-bold text-amber-600 uppercase">Kelas A</p>
                                <p class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ abcBreakdown.A }}</p>
                                <p class="text-xs text-amber-600 mt-1">Produk Sultan (Revenue Tinggi)</p>
                            </div>
                            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                <p class="text-xs font-bold text-blue-600 uppercase">Kelas B</p>
                                <p class="text-3xl font-black text-blue-700 dark:text-blue-400">{{ abcBreakdown.B }}</p>
                                <p class="text-xs text-blue-600 mt-1">Produk Menengah</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
                                <p class="text-xs font-bold text-gray-500 uppercase">Kelas C</p>
                                <p class="text-3xl font-black text-gray-600 dark:text-gray-300">{{ abcBreakdown.C }}</p>
                                <p class="text-xs text-gray-500 mt-1">Produk Minor</p>
                            </div>
                        </div>
                        <!-- Matrix Guide -->
                        <div>
                            <h4 class="font-bold text-gray-800 dark:text-white mb-3">Panduan Matriks ABC/XYZ</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-700">
                                            <th class="p-3 text-left font-bold text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600"></th>
                                            <th class="p-3 text-center font-bold text-green-700 dark:text-green-400 border border-gray-200 dark:border-gray-600">X (Stabil)</th>
                                            <th class="p-3 text-center font-bold text-amber-700 dark:text-amber-400 border border-gray-200 dark:border-gray-600">Y (Fluktuatif)</th>
                                            <th class="p-3 text-center font-bold text-red-700 dark:text-red-400 border border-gray-200 dark:border-gray-600">Z (Tidak Terprediksi)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-3 font-bold text-amber-700 dark:text-amber-400 border border-gray-200 dark:border-gray-600 bg-amber-50 dark:bg-amber-900/20">A</td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center bg-green-50 dark:bg-green-900/10"><span class="text-green-700 font-bold">A-X</span><br><span class="text-xs">Prioritas Utama. Jaga stok.</span></td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center bg-amber-50 dark:bg-amber-900/10"><span class="text-amber-700 font-bold">A-Y</span><br><span class="text-xs">Buffer besar, pantau ketat.</span></td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center bg-red-50 dark:bg-red-900/10"><span class="text-red-700 font-bold">A-Z</span><br><span class="text-xs">Negosiasi MOQ ke supplier.</span></td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 font-bold text-blue-700 dark:text-blue-400 border border-gray-200 dark:border-gray-600 bg-blue-50 dark:bg-blue-900/20">B</td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center"><span class="text-green-600 font-bold">B-X</span><br><span class="text-xs">Reorder rutin, aman.</span></td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center"><span class="text-amber-600 font-bold">B-Y</span><br><span class="text-xs">Evaluasi per kuartal.</span></td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center"><span class="text-red-600 font-bold">B-Z</span><br><span class="text-xs">Cari pola tersembunyi.</span></td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 font-bold text-gray-500 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">C</td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center"><span class="text-gray-600 font-bold">C-X</span><br><span class="text-xs">Stok minimal, display saja.</span></td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center"><span class="text-amber-600 font-bold">C-Y</span><br><span class="text-xs">Evaluasi kelayakan.</span></td>
                                            <td class="p-3 border border-gray-200 dark:border-gray-600 text-center bg-red-50 dark:bg-red-900/10"><span class="text-red-700 font-bold">C-Z</span><br><span class="text-xs text-red-600">Kandidat hapus dari katalog!</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: PRICING -->
                    <div v-if="activeTab === 'pricing'" class="space-y-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-800 dark:text-white">Smart Pricing Assistant</h3>
                            <p class="text-sm text-gray-500 mt-1">Rekomendasi harga berbasis AI berdasarkan tren penjualan dan margin saat ini.</p>
                        </div>
                        <div v-if="pricingInsights.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-4xl mb-3">💡</p>
                            <p class="font-semibold">Belum ada rekomendasi harga aktif.</p>
                            <p class="text-sm mt-1">Jalankan analisa untuk menghasilkan rekomendasi.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="insight in pricingInsights" :key="insight.id"
                                class="p-4 border rounded-xl dark:border-gray-700 hover:border-purple-300 transition-all">
                                <div class="flex items-start gap-3">
                                    <span :class="['px-2 py-1 text-xs font-bold rounded-lg border', severityClass(insight.severity)]">
                                        {{ insight.payload?.action === 'raise' ? '📈 Naikkan' : '📉 Turunkan' }}
                                    </span>
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ insight.title }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ insight.message }}</p>
                                        <div class="flex gap-4 mt-2 text-xs text-gray-600 dark:text-gray-400">
                                            <span v-if="insight.payload?.recommended_price">💰 Rekomendasi: <strong>{{ formatCurrency(insight.payload.recommended_price) }}</strong></span>
                                            <span v-if="insight.payload?.potential_gain">📊 Potensi: <strong>{{ formatCurrency(insight.payload.potential_gain) }}/bln</strong></span>
                                        </div>
                                    </div>
                                    <Link v-if="insight.product" :href="`/products/${insight.product.slug}`"
                                        class="text-xs text-blue-600 hover:underline whitespace-nowrap">Lihat Produk →</Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: CAPITAL -->
                    <div v-if="activeTab === 'capital'" class="space-y-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-800 dark:text-white">Capital Efficiency Advisor</h3>
                            <p class="text-sm text-gray-500 mt-1">Produk dengan perputaran modal rendah — modal bisa lebih produktif di tempat lain.</p>
                        </div>
                        <div v-if="capitalInsights.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-4xl mb-3">📊</p>
                            <p class="font-semibold">Semua produk berefisiensi baik!</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="insight in capitalInsights" :key="insight.id"
                                class="p-4 border rounded-xl dark:border-gray-700 bg-orange-50/50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">{{ insight.product?.name }}</span>
                                            <span class="px-2 py-0.5 text-xs font-black rounded bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">Score {{ insight.payload?.efficiency_score }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">{{ insight.message }}</p>
                                        <div class="flex flex-wrap gap-4 mt-2 text-xs">
                                            <span class="text-gray-600 dark:text-gray-400">💎 Modal Terkunci: <strong>{{ formatCurrency(insight.payload?.capital_locked) }}</strong></span>
                                            <span class="text-gray-600 dark:text-gray-400">🔄 ITR: <strong>{{ insight.payload?.itr }}x/tahun</strong></span>
                                            <span class="text-gray-600 dark:text-gray-400">📅 DSI: <strong>{{ insight.payload?.dsi }} hari</strong></span>
                                        </div>
                                    </div>
                                    <Link v-if="insight.product" :href="`/products/${insight.product.slug}`"
                                        class="text-xs text-blue-600 hover:underline whitespace-nowrap flex-shrink-0">Detail →</Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: SEASONAL -->
                    <div v-if="activeTab === 'seasonal'" class="space-y-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-800 dark:text-white">Seasonal Restocking Planner</h3>
                            <p class="text-sm text-gray-500 mt-1">Prediksi lonjakan permintaan musiman dan rekomendasi waktu pembelian.</p>
                        </div>
                        <div v-if="seasonalInsights.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-4xl mb-3">📅</p>
                            <p class="font-semibold">Tidak ada prediksi puncak musiman dalam 2 bulan ke depan.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="insight in seasonalInsights" :key="insight.id"
                                :class="['p-4 border rounded-xl', insight.severity === 'critical' ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800' : 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800']">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">{{ insight.product?.name }}</span>
                                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', insight.severity === 'critical' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700']">
                                                {{ insight.payload?.urgency?.toUpperCase() }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ insight.message }}</p>
                                        <div class="flex flex-wrap gap-4 mt-2 text-xs">
                                            <span class="text-gray-600 dark:text-gray-400">📦 Perlu Beli: <strong>{{ insight.payload?.restock_needed }} pcs</strong></span>
                                            <span class="text-gray-600 dark:text-gray-400">💰 Estimasi: <strong>{{ formatCurrency(insight.payload?.estimated_cost) }}</strong></span>
                                            <span class="text-gray-600 dark:text-gray-400">⏰ Deadline: <strong>{{ insight.payload?.buy_deadline_days }} hari lagi</strong></span>
                                            <span class="text-gray-600 dark:text-gray-400">📈 Peak: <strong>{{ insight.payload?.peak_month }}</strong></span>
                                        </div>
                                    </div>
                                    <Link :href="`/purchases/create?product_slug=${insight.product?.slug}`"
                                        class="text-xs text-blue-600 hover:underline whitespace-nowrap flex-shrink-0 font-semibold">Beli Sekarang →</Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: BUNDLING -->
                    <div v-if="activeTab === 'bundling'" class="space-y-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-800 dark:text-white">Product Bundling & Association</h3>
                            <p class="text-sm text-gray-500 mt-1">Pasangan produk yang sering dibeli bersamaan (Market Basket Analysis — Apriori).</p>
                        </div>
                        <div v-if="bundlingPairs.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-4xl mb-3">🔗</p>
                            <p class="font-semibold">Belum ada pasangan bundling terdeteksi.</p>
                            <p class="text-sm mt-1">Diperlukan minimal 10 transaksi dalam 90 hari untuk analisa Apriori.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="pair in bundlingPairs" :key="pair.id"
                                class="p-4 border border-gray-100 dark:border-gray-700 rounded-xl hover:border-pink-300 dark:hover:border-pink-700 transition-all">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-900 dark:text-white">{{ pair.title }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ pair.message }}</p>
                                        <div class="flex flex-wrap gap-3 mt-3">
                                            <div class="text-center">
                                                <p class="text-xs text-gray-400 uppercase font-bold">Support</p>
                                                <p class="font-black text-blue-600">{{ ((pair.payload?.support || 0) * 100).toFixed(1) }}%</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs text-gray-400 uppercase font-bold">Confidence</p>
                                                <p class="font-black text-green-600">{{ ((pair.payload?.confidence_pct || pair.payload?.confidence_a_b || 0) * (pair.payload?.confidence_pct ? 1 : 100)).toFixed(0) }}%</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs text-gray-400 uppercase font-bold">Lift</p>
                                                <p class="font-black text-purple-600">{{ pair.payload?.lift || pair.payload?.lift }}</p>
                                                <p class="text-xs text-gray-400">{{ pair.payload?.lift_label }}</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs text-gray-400 uppercase font-bold">Frekuensi</p>
                                                <p class="font-black text-gray-700 dark:text-gray-300">{{ pair.payload?.pair_count || '—' }}x</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: DOCS -->
                    <div v-if="activeTab === 'docs'" class="space-y-4">
                        <h3 class="text-lg font-black text-gray-800 dark:text-white">Dokumentasi Teknis & Pedoman</h3>
                        <div class="space-y-4">
                            <div v-for="feat in intelligenceFeatures" :key="feat.id"
                                class="p-5 border border-gray-100 dark:border-gray-700 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="text-2xl">{{ feat.icon }}</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap mb-2">
                                            <h4 class="font-black text-gray-900 dark:text-white">{{ feat.name }}</h4>
                                            <span :class="['text-xs px-2 py-0.5 rounded-full font-bold', feat.typeColor]">{{ feat.type }}</span>
                                        </div>
                                        <div class="grid md:grid-cols-2 gap-3 text-sm">
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-1">📂 File</p>
                                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-purple-700 dark:text-purple-300">{{ feat.file }}</code>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-1">📥 Input</p>
                                                <div class="flex flex-wrap gap-1">
                                                    <code v-for="k in feat.inputKeys" :key="k" class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded">{{ k }}</code>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-1">📤 Output</p>
                                                <div class="flex flex-wrap gap-1">
                                                    <code v-for="k in feat.outputKeys" :key="k" class="text-xs bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded">{{ k }}</code>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-1">🔄 Logic</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ feat.logic }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-xs font-bold text-gray-400 uppercase mb-1">📍 Digunakan di</p>
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="loc in feat.usedIn" :key="loc" class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full">{{ loc }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
