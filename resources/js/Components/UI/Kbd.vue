<script setup>
defineProps({ keys: { type: [String, Array], required: true } });
</script>

<template>
    <span class="inline-flex gap-1 items-center font-mono">
        <kbd
            v-for="(k, i) in (Array.isArray(keys) ? keys : [keys])"
            :key="i"
            class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-medium text-[color:var(--color-brand-700)] bg-[color:var(--color-brand-50)] border border-[color:var(--color-brand-300)] border-b-2 rounded-[var(--radius-xs)] tracking-wider"
        >
            {{ k }}
        </kbd>
    </span>
</template>
