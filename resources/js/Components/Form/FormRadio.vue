<script setup>
import { computed, useAttrs } from 'vue';
import FormError from './FormError.vue';

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, null], default: null },
    value:      { type: [String, Number, Boolean], required: true },
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    disabled:   { type: Boolean, default: false },
    name:       { type: String, default: null },
});

defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();
const id = computed(() => attrs.id ?? `r-${Math.random().toString(36).slice(2, 9)}`);
const isChecked = computed(() => props.modelValue === props.value);
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
                    type="radio"
                    :name="name"
                    :value="value"
                    :checked="isChecked"
                    :disabled="disabled"
                    @change="$emit('update:modelValue', value)"
                    class="peer sr-only"
                />
                <span
                    class="block w-4 h-4 rounded-full border bg-white transition-all"
                    :class="[
                        isChecked
                            ? 'border-[color:var(--color-accent-500)] border-[5px]'
                            : 'border-[color:var(--color-brand-400)] hover:border-[color:var(--color-brand-600)]',
                        error ? 'border-[color:var(--color-danger)]' : ''
                    ]"
                ></span>
            </span>
            <span class="flex-1">
                <span v-if="label" class="text-sm text-[color:var(--color-ink)] block">{{ label }}</span>
                <slot />
                <span v-if="hint && !error" class="block text-xs text-[color:var(--color-brand-500)] mt-0.5">{{ hint }}</span>
            </span>
        </label>
        <FormError :message="error" />
    </div>
</template>
