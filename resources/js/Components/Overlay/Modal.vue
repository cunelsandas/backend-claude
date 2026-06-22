<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show:      { type: Boolean, required: true },
    title:     { type: String, default: null },
    subtitle:  { type: String, default: null },
    size:      { type: String, default: 'md' }, // sm | md | lg | xl | full
    closable:  { type: Boolean, default: true },
    persistent:{ type: Boolean, default: false }, // ignore overlay click
});
const emit = defineEmits(['close']);

const sizes = {
    sm:  'max-w-md',
    md:  'max-w-lg',
    lg:  'max-w-2xl',
    xl:  'max-w-4xl',
    full:'max-w-[95vw] h-[90vh]',
};

const close = () => {
    if (props.closable) emit('close');
};

const onKey = (e) => {
    if (e.key === 'Escape' && props.show && props.closable) close();
};

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
                class="fixed inset-0 z-50 bg-[color:var(--color-ink)]/40 backdrop-blur-[2px]"
                @click="!persistent && close()"
            />
        </transition>

        <transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-[0.97] translate-y-2"
            leave-active-class="transition-all duration-150"
            leave-to-class="opacity-0 scale-[0.97]"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-[8vh] pointer-events-none"
                role="dialog"
                aria-modal="true"
            >
                <div
                    class="w-full bg-[color:var(--color-card)] rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-pop)] pointer-events-auto flex flex-col max-h-[85vh]"
                    :class="sizes[size]"
                    @click.stop
                >
                    <header
                        v-if="title || $slots.header || closable"
                        class="px-6 pt-5 pb-4 border-b border-[color:var(--color-rule)] flex items-start justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <slot name="header">
                                <h2 v-if="title" class="text-lg font-display font-semibold text-[color:var(--color-ink)] tracking-tight">
                                    {{ title }}
                                </h2>
                                <p v-if="subtitle" class="text-sm text-[color:var(--color-brand-500)] mt-1">{{ subtitle }}</p>
                            </slot>
                        </div>

                        <button
                            v-if="closable"
                            type="button"
                            @click="close"
                            class="text-[color:var(--color-brand-400)] hover:text-[color:var(--color-brand-700)] -mr-1 -mt-1 p-1 rounded transition-colors"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="m4 4 8 8m-8 0 8-8" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </header>

                    <div class="px-6 py-5 overflow-y-auto flex-1">
                        <slot />
                    </div>

                    <footer
                        v-if="$slots.footer"
                        class="px-6 py-4 border-t border-[color:var(--color-rule)] bg-[color:var(--color-brand-50)] rounded-b-[var(--radius-md)] flex items-center justify-end gap-2"
                    >
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </transition>
    </teleport>
</template>
