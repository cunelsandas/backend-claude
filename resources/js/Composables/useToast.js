import { reactive, readonly } from 'vue';

// Global toast registry (singleton across the app).
const state = reactive({
    items: [],
});

let _id = 0;

function push(toast) {
    const id = ++_id;
    state.items.push({
        id,
        tone:    toast.tone ?? 'neutral',  // neutral | success | warning | danger | info | accent
        title:   toast.title ?? null,
        message: toast.message ?? '',
        duration: toast.duration ?? 4500,
        action:  toast.action ?? null,     // { label, onClick }
    });
    if (toast.duration !== 0) {
        setTimeout(() => dismiss(id), toast.duration ?? 4500);
    }
    return id;
}

function dismiss(id) {
    const idx = state.items.findIndex((t) => t.id === id);
    if (idx !== -1) state.items.splice(idx, 1);
}

function clear() {
    state.items.splice(0, state.items.length);
}

const toast = {
    show:    (opts) => push(opts),
    success: (message, opts = {}) => push({ ...opts, tone: 'success', message }),
    error:   (message, opts = {}) => push({ ...opts, tone: 'danger',  message }),
    warn:    (message, opts = {}) => push({ ...opts, tone: 'warning', message }),
    info:    (message, opts = {}) => push({ ...opts, tone: 'info',    message }),
    dismiss,
    clear,
};

export function useToast() {
    return {
        toast,
        state: readonly(state),
        dismiss,
    };
}
