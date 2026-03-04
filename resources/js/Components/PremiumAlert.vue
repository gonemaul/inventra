<template>
  <Teleport to="body">
      <transition
          enter-active-class="transition duration-500 ease-out transform"
          enter-from-class="-translate-y-10 opacity-0 scale-95"
          enter-to-class="translate-y-0 opacity-100 scale-100"
          leave-active-class="transition duration-300 ease-in transform"
          leave-from-class="translate-y-0 opacity-100 scale-100"
          leave-to-class="-translate-y-10 opacity-0 scale-95"
      >
          <div v-if="state.show" class="fixed top-24 left-1/2 transform -translate-x-1/2 z-[100] flex items-center p-4 bg-white/90 backdrop-blur-xl border border-white/50 rounded-2xl shadow-2xl dark:bg-gray-800/95 dark:border-gray-700 min-w-[320px] max-w-[90vw]">
              <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center shadow-inner" :class="iconBgClass">
                  <svg v-if="state.type === 'success'" class="w-6 h-6 text-lime-600 dark:text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                  <svg v-else-if="state.type === 'error'" class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                  <svg v-else-if="state.type === 'warning'" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                  <svg v-else class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div class="ml-4 flex-1">
                  <p class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">{{ state.title }}</p>
                  <p v-if="state.message" class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5" v-html="state.message"></p>
              </div>
          </div>
      </transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { usePremiumAlert } from '@/Composable/usePremiumAlert';

const { state } = usePremiumAlert();

const iconBgClass = computed(() => {
    switch (state.type) {
        case 'success': return 'bg-lime-100 dark:bg-lime-900/50';
        case 'error': return 'bg-red-100 dark:bg-red-900/50';
        case 'warning': return 'bg-yellow-100 dark:bg-yellow-900/50';
        case 'info': return 'bg-blue-100 dark:bg-blue-900/50';
        default: return 'bg-gray-100 dark:bg-gray-900/50';
    }
});
</script>
