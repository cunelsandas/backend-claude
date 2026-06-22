<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone:     { type: String, default: 'neutral' }, // neutral | accent | success | warning | danger | info | brand
    variant:  { type: String, default: 'soft' },    // soft | solid | outline | dot
    size:     { type: String, default: 'md' },      // xs | sm | md
});

const toneMap = {
    neutral: {
        soft:    'bg-[color:var(--color-brand-100)] text-[color:var(--color-brand-800)]',
        solid:   'bg-[color:var(--color-brand-800)] text-white',
        outline: 'border border-[color:var(--color-brand-300)] text-[color:var(--color-brand-700)] bg-transparent',
        dot:     'text-[color:var(--color-brand-700)]',
        dotColor:'bg-[color:var(--color-brand-500)]',
    },
    brand: {
        soft:    'bg-[color:var(--color-brand-200)] text-[color:var(--color-brand-900)]',
        solid:   'bg-[color:var(--color-brand-900)] text-white',
        outline: 'border border-[color:var(--color-brand-400)] text-[color:var(--color-brand-800)] bg-transparent',
        dot:     'text-[color:var(--color-brand-800)]',
        dotColor:'bg-[color:var(--color-brand-700)]',
    },
    accent: {
        soft:    'bg-[color:var(--color-accent-100)] text-[color:var(--color-accent-800)]',
        solid:   'bg-[color:var(--color-accent-500)] text-white',
        outline: 'border border-[color:var(--color-accent-400)] text-[color:var(--color-accent-700)] bg-transparent',
        dot:     'text-[color:var(--color-accent-800)]',
        dotColor:'bg-[color:var(--color-accent-500)]',
    },
    success: {
        soft:    'bg-emerald-50 text-emerald-800',
        solid:   'bg-emerald-700 text-white',
        outline: 'border border-emerald-300 text-emerald-700 bg-transparent',
        dot:     'text-emerald-800',
        dotColor:'bg-emerald-500',
    },
    warning: {
        soft:    'bg-amber-50 text-amber-800',
        solid:   'bg-amber-600 text-white',
        outline: 'border border-amber-300 text-amber-700 bg-transparent',
        dot:     'text-amber-800',
        dotColor:'bg-amber-500',
    },
    danger: {
        soft:    'bg-rose-50 text-rose-800',
        solid:   'bg-rose-700 text-white',
        outline: 'border border-rose-300 text-rose-700 bg-transparent',
        dot:     'text-rose-800',
        dotColor:'bg-rose-500',
    },
    info: {
        soft:    'bg-sky-50 text-sky-800',
        solid:   'bg-sky-700 text-white',
        outline: 'border border-sky-300 text-sky-700 bg-transparent',
        dot:     'text-sky-800',
        dotColor:'bg-sky-500',
    },
};

const sizeMap = {
    xs: 'text-[10px] px-1.5 py-0.5 gap-1',
    sm: 'text-[11px] px-2   py-0.5 gap-1',
    md: 'text-xs     px-2   py-0.5 gap-1.5',
};

const toneStyle = computed(() => toneMap[props.tone]?.[props.variant] ?? toneMap.neutral.soft);
const dotColor  = computed(() => toneMap[props.tone]?.dotColor ?? toneMap.neutral.dotColor);
</script>

<template>
    <span
        class="inline-flex items-center font-medium rounded-[var(--radius-xs)] tracking-wide uppercase"
        :class="[toneStyle, sizeMap[size]]"
    >
        <span
            v-if="variant === 'dot'"
            class="inline-block w-1.5 h-1.5 rounded-full"
            :class="dotColor"
        />
        <slot />
    </span>
</template>
