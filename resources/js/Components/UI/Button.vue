<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: { type: String, default: 'solid' },     // solid | outline | ghost | link | danger
    tone:    { type: String, default: 'primary' },   // primary | accent | neutral
    size:    { type: String, default: 'md' },        // xs | sm | md | lg
    icon:    { type: Boolean, default: false },      // square (icon-only)
    loading: { type: Boolean, default: false },
    disabled:{ type: Boolean, default: false },
    href:    { type: String, default: null },        // if set, renders as Inertia Link
    as:      { type: String, default: 'button' },    // override tag
    type:    { type: String, default: 'button' },
});

const sizing = {
    xs: 'h-7  text-[11px] px-2.5 gap-1',
    sm: 'h-8  text-xs     px-3   gap-1.5',
    md: 'h-9  text-sm     px-3.5 gap-2',
    lg: 'h-11 text-sm     px-5   gap-2',
};

const iconSizing = {
    xs: 'h-7 w-7',
    sm: 'h-8 w-8',
    md: 'h-9 w-9',
    lg: 'h-11 w-11',
};

const toneClasses = computed(() => {
    const { variant, tone, disabled, loading } = props;
    const isDisabled = disabled || loading;

    // SOLID
    if (variant === 'solid') {
        if (tone === 'primary')  return 'bg-[color:var(--color-brand-900)] text-white hover:bg-[color:var(--color-brand-800)] active:bg-black border border-[color:var(--color-brand-900)]';
        if (tone === 'accent')   return 'bg-[color:var(--color-accent-500)] text-white hover:bg-[color:var(--color-accent-600)] active:bg-[color:var(--color-accent-700)] border border-[color:var(--color-accent-500)]';
        if (tone === 'neutral')  return 'bg-[color:var(--color-brand-100)] text-[color:var(--color-brand-900)] hover:bg-[color:var(--color-brand-200)] border border-[color:var(--color-brand-200)]';
    }
    if (variant === 'danger')    return 'bg-[color:var(--color-danger)] text-white hover:brightness-110 active:brightness-95 border border-[color:var(--color-danger)]';

    // OUTLINE
    if (variant === 'outline') {
        if (tone === 'accent')   return 'bg-transparent text-[color:var(--color-accent-700)] hover:bg-[color:var(--color-accent-50)] border border-[color:var(--color-accent-300)]';
        return 'bg-transparent text-[color:var(--color-brand-800)] hover:bg-[color:var(--color-brand-50)] border border-[color:var(--color-brand-300)]';
    }

    // GHOST
    if (variant === 'ghost') {
        return 'bg-transparent text-[color:var(--color-brand-700)] hover:bg-[color:var(--color-brand-100)] border border-transparent';
    }

    // LINK
    if (variant === 'link') {
        return 'bg-transparent text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] underline underline-offset-2 decoration-1 hover:decoration-2 border border-transparent px-0';
    }

    return '';
});

const baseClasses = computed(() => [
    'inline-flex items-center justify-center font-medium rounded-[var(--radius-sm)]',
    'transition-all duration-150 ease-out',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    'select-none whitespace-nowrap',
    props.icon ? iconSizing[props.size] : sizing[props.size],
    toneClasses.value,
].join(' '));

const tag = computed(() => (props.href ? Link : props.as));
</script>

<template>
    <component
        :is="tag"
        :href="href"
        :type="!href ? type : undefined"
        :disabled="disabled || loading"
        :class="baseClasses"
    >
        <svg
            v-if="loading"
            class="animate-spin h-3.5 w-3.5"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/>
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
        </svg>
        <slot />
    </component>
</template>
