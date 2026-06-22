<script setup>
import { computed, useAttrs } from 'vue';
import FormLabel from './FormLabel.vue';
import FormError from './FormError.vue';
import FormHint from './FormHint.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    rows:       { type: Number, default: 4 },
    required:   { type: Boolean, default: false },
    maxlength:  { type: Number, default: null },
    showCount:  { type: Boolean, default: false },
    mono:       { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();
const id = computed(() => attrs.id ?? `t-${Math.random().toString(36).slice(2, 9)}`);

const count = computed(() => (props.modelValue ?? '').length);
</script>

<template>
    <div>
        <div class="flex items-end justify-between">
            <FormLabel v-if="label" :for="id" :required="required">{{ label }}</FormLabel>
            <span
                v-if="showCount && maxlength"
                class="text-[10px] font-mono tabular-nums text-[color:var(--color-brand-500)] mb-1.5"
            >
                {{ count }} / {{ maxlength }}
            </span>
        </div>

        <textarea
            :id="id"
            v-bind="attrs"
            :value="modelValue"
            :rows="rows"
            :maxlength="maxlength"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full px-3 py-2 bg-[color:var(--color-card)] text-[color:var(--color-ink)] placeholder-[color:var(--color-brand-400)] border rounded-[var(--radius-sm)] transition-colors focus:outline-none focus:ring-0 resize-y"
            :class="[
                error
                    ? 'border-[color:var(--color-danger)]'
                    : 'border-[color:var(--color-brand-300)] hover:border-[color:var(--color-brand-400)] focus:border-[color:var(--color-accent-500)] focus:shadow-[0_0_0_3px_var(--color-accent-100)]',
                mono ? 'font-mono text-xs leading-relaxed' : 'text-sm',
            ]"
        ></textarea>

        <FormError :message="error" />
        <FormHint v-if="hint && !error">{{ hint }}</FormHint>
    </div>
</template>
