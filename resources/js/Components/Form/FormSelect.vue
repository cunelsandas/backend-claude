<script setup>
import { computed, useAttrs } from 'vue';
import FormLabel from './FormLabel.vue';
import FormError from './FormError.vue';
import FormHint from './FormHint.vue';

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, null], default: '' },
    options:    { type: Array, required: true }, // [{value, label, disabled?}] OR ['a', 'b'] OR [{value, label}]
    placeholder:{ type: String, default: null },
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    size:       { type: String, default: 'md' },
    required:   { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();
const id = computed(() => attrs.id ?? `s-${Math.random().toString(36).slice(2, 9)}`);

const normalized = computed(() =>
    props.options.map((o) =>
        typeof o === 'object' && o !== null
            ? { value: o.value, label: o.label ?? String(o.value), disabled: !!o.disabled }
            : { value: o, label: String(o), disabled: false }
    )
);

const sizing = { sm: 'h-8 text-xs', md: 'h-9 text-sm', lg: 'h-11 text-sm' };
</script>

<template>
    <div>
        <FormLabel v-if="label" :for="id" :required="required">{{ label }}</FormLabel>

        <div class="relative">
            <select
                :id="id"
                v-bind="attrs"
                :value="modelValue"
                @change="$emit('update:modelValue', $event.target.value)"
                class="w-full appearance-none bg-[color:var(--color-card)] text-[color:var(--color-ink)] pl-3 pr-9 border rounded-[var(--radius-sm)] transition-colors focus:outline-none focus:ring-0 disabled:bg-[color:var(--color-brand-50)] disabled:text-[color:var(--color-brand-400)]"
                :class="[
                    error
                        ? 'border-[color:var(--color-danger)]'
                        : 'border-[color:var(--color-brand-300)] hover:border-[color:var(--color-brand-400)] focus:border-[color:var(--color-accent-500)] focus:shadow-[0_0_0_3px_var(--color-accent-100)]',
                    sizing[size],
                ]"
            >
                <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
                <option
                    v-for="opt in normalized"
                    :key="opt.value"
                    :value="opt.value"
                    :disabled="opt.disabled"
                >
                    {{ opt.label }}
                </option>
            </select>

            <svg
                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-brand-500)] pointer-events-none"
                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"
            >
                <path d="m4 6 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <FormError :message="error" />
        <FormHint v-if="hint && !error">{{ hint }}</FormHint>
    </div>
</template>
