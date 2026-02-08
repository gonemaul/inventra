<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <div class="print-layout bg-white min-h-screen text-black">
        <div class="p-8">
            <!-- Header Print -->
            <div class="flex items-center justify-between mb-8 border-b-2 border-black pb-4">
                <div>
                     <h1 class="text-2xl font-black uppercase tracking-wider">Laporan Inventra</h1>
                     <p class="text-sm">Generated on {{ new Date().toLocaleString('id-ID') }}</p>
                </div>
                <div class="text-right">
                    <h2 class="text-lg font-bold">Inventra System</h2>
                </div>
            </div>

            <!-- Content -->
            <slot />

            <!-- Footer Print -->
             <div class="mt-8 pt-4 border-t border-gray-300 text-center text-xs text-gray-500">
                &copy; {{ new Date().getFullYear() }} Inventra. Confidential Document.
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 0.5cm;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .no-print {
        display: none !important;
    }
}
</style>
