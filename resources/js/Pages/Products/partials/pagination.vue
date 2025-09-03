<template>
    <div
        class="flex flex-col items-center justify-between gap-4 mt-6 md:flex-row"
    >
        <!-- Info jumlah data -->
        <div class="text-sm text-gray-600 dark:text-gray-200">
            Menampilkan <span class="font-semibold">1</span> -
            <span class="font-semibold">10</span> dari
            <span class="font-semibold">120</span> data
        </div>

        <!-- Pagination -->
        <div class="flex items-center gap-2">
            <!-- Previous -->
            <button
                class="px-3 py-1 text-sm text-gray-400 bg-gray-200 rounded-md cursor-not-allowed"
            >
                ‹
            </button>

            <!-- Pages -->
            <button class="px-3 py-1 text-sm text-white rounded-md bg-lime-600">
                1
            </button>
            <button
                class="px-3 py-1 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-lime-300"
            >
                2
            </button>
            <button
                class="px-3 py-1 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-lime-300"
            >
                3
            </button>
            <span class="px-2">...</span>
            <button
                class="px-3 py-1 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-lime-300"
            >
                12
            </button>

            <!-- Next -->
            <button
                class="px-3 py-1 text-sm text-white rounded-md bg-lime-500 hover:bg-lime-600"
            >
                ›
            </button>
        </div>

        <!-- Page size -->
        <div class="flex items-center gap-2">
            <label
                for="pageSize"
                class="text-sm text-gray-600 dark:text-gray-200"
                >Tampilkan:</label
            >
            <select
                id="pageSize"
                class="px-2 py-1 text-sm border-2 border-gray-500 rounded-md w-14 dark:bg-gray-700 dark:text-white"
            >
                <option>5</option>
                <option selected>10</option>
                <option>20</option>
                <option>50</option>
            </select>
        </div>
    </div>
</template>
