<script setup>
defineProps({ message: { type: String, default: null } });
</script>

<template>
    <transition
        enter-active-class="transition-all duration-150"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition-all duration-100"
        leave-to-class="opacity-0"
    >
        <p
            v-if="message"
            class="mt-1.5 text-xs text-[color:var(--color-danger)] flex items-start gap-1"
        >
            <svg class="w-3.5 h-3.5 mt-px shrink-0" viewBox="0 0 16 16" fill="currentColor">
                <path fill-rule="evenodd" d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13ZM7.25 4.75a.75.75 0 0 1 1.5 0v4a.75.75 0 0 1-1.5 0v-4ZM8 12a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
            </svg>
            <span>{{ message }}</span>
        </p>
    </transition>
</template>
