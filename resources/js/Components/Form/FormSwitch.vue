<script setup>
import { computed, useAttrs } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label:      { type: String, default: null },
    hint:       { type: String, default: null },
    disabled:   { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();
const id = computed(() => attrs.id ?? `sw-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
    <label :for="id" class="flex items-center gap-3 cursor-pointer select-none" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
        <span class="relative inline-block">
            <input
                :id="id"
                type="checkbox"
                :checked="modelValue"
                :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.checked)"
                class="peer sr-only"
            />
            <span
                class="block w-9 h-5 rounded-full transition-colors"
                :class="modelValue ? 'bg-[color:var(--color-accent-500)]' : 'bg-[color:var(--color-brand-300)]'"
            ></span>
            <span
                class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform"
                :class="modelValue ? 'translate-x-4' : ''"
            ></span>
        </span>
        <span v-if="label || hint" class="flex-1">
            <span v-if="label" class="text-sm text-[color:var(--color-ink)] block">{{ label }}</span>
            <span v-if="hint" class="block text-xs text-[color:var(--color-brand-500)]">{{ hint }}</span>
        </span>
    </label>
</template>
