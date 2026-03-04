import { reactive } from 'vue';

const state = reactive({
    show: false,
    type: 'success', // 'success', 'error', 'warning', 'info'
    title: '',
    message: '',
});

let timeout = null;

const triggerAlert = (type, title, message = '') => {
    state.type = type;
    state.title = title;
    state.message = message;
    state.show = true;

    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        state.show = false;
    }, 3000);
};

export function usePremiumAlert() {
    return {
        state,
        success: (msgOrTitle, msg) => {
            if (msg) triggerAlert('success', msgOrTitle, msg);
            else triggerAlert('success', 'Berhasil', msgOrTitle);
        },
        error: (msgOrTitle, msg) => {
            if (msg) triggerAlert('error', msgOrTitle, msg);
            else triggerAlert('error', 'Gagal', msgOrTitle);
        },
        warning: (msgOrTitle, msg) => {
            if (msg) triggerAlert('warning', msgOrTitle, msg);
            else triggerAlert('warning', 'Peringatan', msgOrTitle);
        },
        info: (msgOrTitle, msg) => {
            if (msg) triggerAlert('info', msgOrTitle, msg);
            else triggerAlert('info', 'Informasi', msgOrTitle);
        },
        custom: (type, title, message) => triggerAlert(type, title, message)
    };
}
