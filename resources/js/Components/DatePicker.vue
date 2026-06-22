<script setup>
import { onBeforeUnmount, onMounted, ref, useAttrs, watch } from 'vue';
import flatpickr from 'flatpickr';
import FormLabel from './Form/FormLabel.vue';
import FormError from './Form/FormError.vue';
import FormHint from './Form/FormHint.vue';

const props = defineProps({
    modelValue: { type: [String, Array, null], default: null },
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    placeholder:{ type: String, default: 'Select date…' },
    required:   { type: Boolean, default: false },
    mode:       { type: String, default: 'single' },        // single | range | multiple
    minDate:    { type: [String, Date, null], default: null },
    maxDate:    { type: [String, Date, null], default: null },
    format:     { type: String, default: 'Y-m-d' },         // flatpickr format
    altFormat:  { type: String, default: 'M j, Y' },        // human display
    enableTime: { type: Boolean, default: false },
    size:       { type: String, default: 'md' },
    clearable:  { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();

const inputRef = ref(null);
let instance = null;

const sizing = { sm: 'h-8 text-xs', md: 'h-9 text-sm', lg: 'h-11 text-sm' };

onMounted(() => {
    instance = flatpickr(inputRef.value, {
        mode: props.mode,
        dateFormat: props.format,
        altInput: true,
        altFormat: props.altFormat,
        minDate: props.minDate,
        maxDate: props.maxDate,
        enableTime: props.enableTime,
        defaultDate: props.modelValue,
        onChange: (selectedDates, dateStr) => {
            if (props.mode === 'range') {
                emit('update:modelValue',
                    selectedDates.length === 2
                        ? selectedDates.map(d => flatpickr.formatDate(d, props.format))
                        : []
                );
            } else if (props.mode === 'multiple') {
                emit('update:modelValue', selectedDates.map(d => flatpickr.formatDate(d, props.format)));
            } else {
                emit('update:modelValue', dateStr);
            }
        },
    });
});

onBeforeUnmount(() => {
    instance?.destroy();
});

watch(() => props.modelValue, (v) => {
    if (instance && JSON.stringify(instance.selectedDates.map(d => flatpickr.formatDate(d, props.format))) !== JSON.stringify(Array.isArray(v) ? v : [v])) {
        instance.setDate(v ?? '', false);
    }
});

const clear = () => {
    instance?.clear();
    emit('update:modelValue', props.mode === 'range' ? [] : null);
};
</script>

<template>
    <div>
        <FormLabel v-if="label" :required="required">{{ label }}</FormLabel>

        <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-brand-500)] pointer-events-none z-10" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="3" width="12" height="11" rx="1"/><path d="M2 6h12M5 1.5v3M11 1.5v3" stroke-linecap="round"/>
            </svg>

            <input
                ref="inputRef"
                v-bind="attrs"
                :placeholder="placeholder"
                class="w-full bg-[color:var(--color-card)] text-[color:var(--color-ink)] placeholder-[color:var(--color-brand-400)] border rounded-[var(--radius-sm)] pl-9 pr-9 focus:outline-none transition-colors"
                :class="[
                    error
                        ? 'border-[color:var(--color-danger)]'
                        : 'border-[color:var(--color-brand-300)] hover:border-[color:var(--color-brand-400)] focus:border-[color:var(--color-accent-500)] focus:shadow-[0_0_0_3px_var(--color-accent-100)]',
                    sizing[size]
                ]"
            />

            <button
                v-if="clearable && modelValue && (!Array.isArray(modelValue) || modelValue.length)"
                type="button"
                @click="clear"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[color:var(--color-brand-400)] hover:text-[color:var(--color-brand-700)]"
            >
                <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="m4 4 8 8m-8 0 8-8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <FormError :message="error"/>
        <FormHint v-if="hint && !error">{{ hint }}</FormHint>
    </div>
</template>
