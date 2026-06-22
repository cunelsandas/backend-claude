<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show:     { type: Boolean, required: true },
    title:    { type: String, default: null },
    subtitle: { type: String, default: null },
    side:     { type: String, default: 'right' }, // right | left
    width:    { type: String, default: 'md' },    // sm | md | lg | xl
    closable: { type: Boolean, default: true },
});
const emit = defineEmits(['close']);

const widths = {
    sm: 'w-[360px]',
    md: 'w-[480px]',
    lg: 'w-[640px]',
    xl: 'w-[820px]',
};

const close = () => props.closable && emit('close');

const onKey = (e) => { if (e.key === 'Escape' && props.show) close(); };
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));

watch(() => props.show, (v) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = v ? 'hidden' : '';
});
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 bg-[color:var(--color-ink)]/30 backdrop-blur-[2px]"
                @click="close"
            />
        </transition>

        <transition
            :enter-active-class="`transition-transform duration-300 ease-out`"
            :enter-from-class="side === 'right' ? 'translate-x-full' : '-translate-x-full'"
            :leave-active-class="`transition-transform duration-200 ease-in`"
            :leave-to-class="side === 'right' ? 'translate-x-full' : '-translate-x-full'"
        >
            <aside
                v-if="show"
                class="fixed top-0 bottom-0 z-50 bg-[color:var(--color-card)] shadow-[var(--shadow-pop)] flex flex-col"
                :class="[widths[width], side === 'right' ? 'right-0 border-l' : 'left-0 border-r', 'border-[color:var(--color-rule)]']"
                role="dialog"
                aria-modal="true"
                @click.stop
            >
                <header class="h-14 px-5 border-b border-[color:var(--color-rule)] flex items-center justify-between shrink-0">
                    <div class="min-w-0">
                        <slot name="header">
                            <h2 v-if="title" class="text-sm font-semibold text-[color:var(--color-ink)] truncate">{{ title }}</h2>
                            <p v-if="subtitle" class="text-xs text-[color:var(--color-brand-500)] truncate">{{ subtitle }}</p>
                        </slot>
                    </div>
                    <button
                        v-if="closable"
                        type="button"
                        @click="close"
                        class="text-[color:var(--color-brand-400)] hover:text-[color:var(--color-brand-700)] p-1 rounded"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="m4 4 8 8m-8 0 8-8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <slot />
                </div>

                <footer
                    v-if="$slots.footer"
                    class="px-5 py-3 border-t border-[color:var(--color-rule)] bg-[color:var(--color-brand-50)] flex items-center justify-end gap-2 shrink-0"
                >
                    <slot name="footer" />
                </footer>
            </aside>
        </transition>
    </teleport>
</template>
