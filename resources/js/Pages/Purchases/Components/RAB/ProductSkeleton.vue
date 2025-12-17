<template>
    <div class="space-y-3 animate-pulse">
        <div
            v-for="i in 5"
            :key="i"
            class="flex items-center p-3 bg-white border rounded-lg"
        >
            <div class="flex-shrink-0 w-12 h-12 mr-4 bg-gray-200 rounded"></div>

            <div class="flex-1 space-y-2">
                <div class="w-3/4 h-4 bg-gray-200 rounded"></div>
                <div class="w-1/2 h-3 bg-gray-200 rounded"></div>
            </div>

            <div class="w-16 h-8 ml-auto bg-gray-200 rounded"></div>
        </div>
    </div>
</template>
