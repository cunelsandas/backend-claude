<script setup>
import { useToast } from '@/Composables/useToast';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const { state, dismiss, toast } = useToast();
const page = usePage();

// Auto-pipe Inertia flash messages → toasts.
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) toast.success(flash.success);
        if (flash.error)   toast.error(flash.error);
    },
    { deep: true, immediate: true }
);

const toneClass = {
    neutral: 'border-[color:var(--color-rule)] bg-white',
    success: 'border-emerald-200 bg-emerald-50',
    warning: 'border-amber-200 bg-amber-50',
    danger:  'border-rose-200 bg-rose-50',
    info:    'border-sky-200 bg-sky-50',
    accent:  'border-[color:var(--color-accent-300)] bg-[color:var(--color-accent-50)]',
};

const iconColor = {
    neutral: 'text-[color:var(--color-brand-500)]',
    success: 'text-emerald-600',
    warning: 'text-amber-600',
    danger:  'text-rose-600',
    info:    'text-sky-600',
    accent:  'text-[color:var(--color-accent-600)]',
};

const icon = (tone) => {
    switch (tone) {
        case 'success': return 'M5 12l4 4 10-10';
        case 'danger':
        case 'warning': return 'M12 9v3.5m0 3h.01M5.07 19h13.86a2 2 0 0 0 1.74-3l-6.93-12a2 2 0 0 0-3.48 0l-6.93 12a2 2 0 0 0 1.74 3Z';
        case 'info':    return 'M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
        default:        return 'M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
    }
};
</script>

<template>
    <teleport to="body">
        <div class="fixed bottom-6 right-6 z-[60] flex flex-col items-end gap-2 pointer-events-none w-[360px] max-w-[calc(100vw-3rem)]">
            <transition-group
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-x-6 scale-95"
                leave-active-class="transition-all duration-200 ease-in"
                leave-to-class="opacity-0 translate-x-6"
                move-class="transition-transform duration-200"
            >
                <div
                    v-for="t in state.items"
                    :key="t.id"
                    class="pointer-events-auto w-full border rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] flex items-start gap-3 px-4 py-3"
                    :class="toneClass[t.tone]"
                >
                    <svg
                        class="w-4 h-4 mt-0.5 shrink-0"
                        :class="iconColor[t.tone]"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    >
                        <path :d="icon(t.tone)" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                    <div class="flex-1 min-w-0">
                        <p v-if="t.title" class="text-sm font-semibold text-[color:var(--color-ink)]">{{ t.title }}</p>
                        <p class="text-[13px] text-[color:var(--color-brand-700)] leading-snug">{{ t.message }}</p>

                        <button
                            v-if="t.action"
                            type="button"
                            class="mt-1 text-xs font-semibold text-[color:var(--color-accent-700)] hover:text-[color:var(--color-accent-800)]"
                            @click="t.action.onClick(); dismiss(t.id);"
                        >
                            {{ t.action.label }} →
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="dismiss(t.id)"
                        class="text-[color:var(--color-brand-400)] hover:text-[color:var(--color-brand-700)] shrink-0 p-0.5"
                    >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="m4 4 8 8m-8 0 8-8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </transition-group>
        </div>
    </teleport>
</template>
