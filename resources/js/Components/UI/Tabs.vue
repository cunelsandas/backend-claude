<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], required: true },
    tabs:       { type: Array, required: true },  // [{key, label, badge?, disabled?}]
    variant:    { type: String, default: 'underline' },  // underline | pill
    size:       { type: String, default: 'md' },         // sm | md
});
const emit = defineEmits(['update:modelValue']);

const select = (tab) => {
    if (tab.disabled) return;
    emit('update:modelValue', tab.key);
};

const isActive = (tab) => props.modelValue === tab.key;
</script>

<template>
    <div v-if="variant === 'underline'" class="border-b border-[color:var(--color-rule)]">
        <nav class="flex gap-6 -mb-px" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                role="tab"
                :aria-selected="isActive(tab)"
                :disabled="tab.disabled"
                @click="select(tab)"
                class="group flex items-center gap-2 pb-3 border-b-2 transition-colors text-sm font-medium disabled:opacity-40"
                :class="[
                    isActive(tab)
                        ? 'border-[color:var(--color-accent-500)] text-[color:var(--color-ink)]'
                        : 'border-transparent text-[color:var(--color-brand-500)] hover:text-[color:var(--color-brand-800)] hover:border-[color:var(--color-brand-300)]',
                    size === 'sm' ? 'text-xs pb-2' : ''
                ]"
            >
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge != null"
                    class="text-[10px] font-mono tabular-nums px-1.5 rounded-[var(--radius-xs)]"
                    :class="isActive(tab)
                        ? 'bg-[color:var(--color-accent-100)] text-[color:var(--color-accent-800)]'
                        : 'bg-[color:var(--color-brand-100)] text-[color:var(--color-brand-700)]'"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </nav>
    </div>

    <div v-else class="inline-flex p-1 bg-[color:var(--color-brand-100)] rounded-[var(--radius-sm)] gap-1">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            :disabled="tab.disabled"
            @click="select(tab)"
            class="px-3 py-1.5 text-xs font-medium rounded-[var(--radius-xs)] transition-all disabled:opacity-40"
            :class="isActive(tab)
                ? 'bg-white text-[color:var(--color-ink)] shadow-[var(--shadow-paper)]'
                : 'text-[color:var(--color-brand-600)] hover:text-[color:var(--color-brand-800)]'"
        >
            {{ tab.label }}
        </button>
    </div>
</template>
