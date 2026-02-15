import { ref, onMounted, onUnmounted } from 'vue';

// export function useWakeLock(idleTime = 120000) { // Default 2 minutes
export function useWakeLock(idleTime = 30000) { // Default 2 minutes
    const isDimmed = ref(false);
    let wakeLock = null;
    let idleTimer = null;

    // Request Screen Wake Lock
    const requestWakeLock = async () => {
        try {
            if ('wakeLock' in navigator) {
                wakeLock = await navigator.wakeLock.request('screen');
                // console.log('Wake Lock is active');

                wakeLock.addEventListener('release', () => {
                    // console.log('Wake Lock was released');
                });
            }
        } catch (err) {
            console.error(`${err.name}, ${err.message}`);
        }
    };

    // Release Screen Wake Lock
    const releaseWakeLock = async () => {
        if (wakeLock !== null) {
            await wakeLock.release();
            wakeLock = null;
        }
    };

    // Handle Visibility Change (Re-request lock if tab becomes visible again)
    const handleVisibilityChange = async () => {
        if (wakeLock !== null && document.visibilityState === 'visible') {
            await requestWakeLock();
        }
    };

    // Reset Idle Timer
    const resetIdleTimer = () => {
        isDimmed.value = false;
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            isDimmed.value = true;
        }, idleTime);
    };

    // Setup Event Listeners for Activity
    const setupActivityListeners = () => {
        const events = ['mousemove', 'keydown', 'touchstart', 'click', 'scroll'];
        events.forEach(event => {
            window.addEventListener(event, resetIdleTimer);
        });
    };

    // Cleanup Event Listeners
    const cleanupActivityListeners = () => {
        const events = ['mousemove', 'keydown', 'touchstart', 'click', 'scroll'];
        events.forEach(event => {
            window.removeEventListener(event, resetIdleTimer);
        });
    };

    onMounted(() => {
        requestWakeLock();
        document.addEventListener('visibilitychange', handleVisibilityChange);
        setupActivityListeners();
        resetIdleTimer(); // Start timer
    });

    onUnmounted(() => {
        releaseWakeLock();
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        cleanupActivityListeners();
        if (idleTimer) clearTimeout(idleTimer);
    });

    return {
        isDimmed,
        requestWakeLock,
        releaseWakeLock,
        resetIdleTimer
    };
}
