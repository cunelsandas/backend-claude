<script setup>
import { computed, useAttrs } from 'vue';
import FormLabel from './FormLabel.vue';
import FormError from './FormError.vue';
import FormHint from './FormHint.vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    type:       { type: String, default: 'text' },
    size:       { type: String, default: 'md' },     // sm | md | lg
    required:   { type: Boolean, default: false },
    optional:   { type: Boolean, default: false },
    leadingIcon:  { type: Boolean, default: false },
    trailingIcon: { type: Boolean, default: false },
    mono:       { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();

const id = computed(() => attrs.id ?? `f-${Math.random().toString(36).slice(2, 9)}`);

const sizing = {
    sm: 'h-8  text-xs',
    md: 'h-9  text-sm',
    lg: 'h-11 text-sm',
};

const inputClass = computed(() => [
    'w-full bg-[color:var(--color-card)] text-[color:var(--color-ink)] placeholder-[color:var(--color-brand-400)]',
    'border rounded-[var(--radius-sm)] transition-colors',
    'focus:outline-none focus:ring-0',
    props.error
        ? 'border-[color:var(--color-danger)] focus:border-[color:var(--color-danger)]'
        : 'border-[color:var(--color-brand-300)] hover:border-[color:var(--color-brand-400)] focus:border-[color:var(--color-accent-500)] focus:shadow-[0_0_0_3px_var(--color-accent-100)]',
    props.mono ? 'font-mono' : '',
    sizing[props.size],
    props.leadingIcon ? 'pl-9' : 'pl-3',
    props.trailingIcon ? 'pr-9' : 'pr-3',
    'disabled:bg-[color:var(--color-brand-50)] disabled:text-[color:var(--color-brand-400)] disabled:cursor-not-allowed',
]);
</script>

<template>
    <div>
        <FormLabel v-if="label" :for="id" :required="required" :optional="optional">{{ label }}</FormLabel>

        <div class="relative">
            <span
                v-if="leadingIcon || $slots.leading"
                class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[color:var(--color-brand-500)] pointer-events-none flex items-center"
            >
                <slot name="leading" />
            </span>

            <input
                :id="id"
                v-bind="attrs"
                :type="type"
                :value="modelValue"
                :class="inputClass"
                @input="$emit('update:modelValue', $event.target.value)"
            />

            <span
                v-if="trailingIcon || $slots.trailing"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[color:var(--color-brand-500)] flex items-center"
            >
                <slot name="trailing" />
            </span>
        </div>

        <FormError :message="error" />
        <FormHint v-if="hint && !error">{{ hint }}</FormHint>
    </div>
</template>
