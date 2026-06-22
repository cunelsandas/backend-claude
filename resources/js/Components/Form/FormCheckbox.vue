<script setup>
import { computed, useAttrs } from 'vue';
import FormError from './FormError.vue';

const props = defineProps({
    modelValue: { type: [Boolean, Array], default: false },
    value:      { type: [String, Number, Boolean], default: null }, // when used as array-of-values group
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    disabled:   { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();
const id = computed(() => attrs.id ?? `c-${Math.random().toString(36).slice(2, 9)}`);

const isChecked = computed(() => {
    if (Array.isArray(props.modelValue)) return props.modelValue.includes(props.value);
    return !!props.modelValue;
});

const toggle = (e) => {
    if (Array.isArray(props.modelValue)) {
        const next = [...props.modelValue];
        const idx = next.indexOf(props.value);
        if (e.target.checked && idx === -1) next.push(props.value);
        if (!e.target.checked && idx !== -1) next.splice(idx, 1);
        emit('update:modelValue', next);
    } else {
        emit('update:modelValue', e.target.checked);
    }
};
</script>

<template>
    <div>
        <label
            :for="id"
            class="flex items-start gap-2.5 cursor-pointer select-none"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }"
        >
            <span class="relative flex-shrink-0 mt-0.5">
                <input
                    :id="id"
                    v-bind="attrs"
                    type="checkbox"
                    :checked="isChecked"
                    :disabled="disabled"
                    @change="toggle"
                    class="peer sr-only"
                />
                <span
                    class="block w-4 h-4 border rounded-[var(--radius-xs)] bg-white transition-all"
                    :class="[
                        isChecked
                            ? 'bg-[color:var(--color-accent-500)] border-[color:var(--color-accent-500)]'
                            : 'border-[color:var(--color-brand-400)] hover:border-[color:var(--color-brand-600)]',
                        error ? 'border-[color:var(--color-danger)]' : ''
                    ]"
                ></span>
                <svg
                    v-if="isChecked"
                    class="absolute top-0.5 left-0.5 w-3 h-3 text-white pointer-events-none"
                    viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"
                >
                    <path d="m2.5 6.5 2.5 2.5 4.5-5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>

            <span class="flex-1 min-w-0">
                <span v-if="label" class="text-sm text-[color:var(--color-ink)] block">{{ label }}</span>
                <slot />
                <span v-if="hint && !error" class="block text-xs text-[color:var(--color-brand-500)] mt-0.5">{{ hint }}</span>
            </span>
        </label>
        <FormError :message="error" />
    </div>
</template>
