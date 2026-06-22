<script setup>
defineProps({
    for: { type: String, default: null },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
});
</script>

<template>
    <label
        :for="$props.for"
        class="block text-xs font-medium text-[color:var(--color-brand-800)] uppercase tracking-wider mb-1.5"
    >
        <slot />
        <span v-if="required" class="text-[color:var(--color-accent-600)] ml-0.5">*</span>
        <span v-if="optional" class="text-[color:var(--color-brand-400)] normal-case font-normal tracking-normal ml-1">(optional)</span>
    </label>
</template>
