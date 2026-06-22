<script setup>
import Modal from './Modal.vue';
import Button from '../UI/Button.vue';

const props = defineProps({
    show:        { type: Boolean, required: true },
    title:       { type: String, default: 'Are you sure?' },
    message:     { type: String, default: 'This action cannot be undone.' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText:  { type: String, default: 'Cancel' },
    tone:        { type: String, default: 'danger' }, // danger | accent | primary
    loading:     { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'confirm']);

const variantMap = {
    danger: 'danger',
    accent: 'solid',     // tone='accent'
    primary:'solid',
};
const toneMap = {
    danger: 'danger',
    accent: 'accent',
    primary:'primary',
};
const iconMap = {
    danger:  'text-[color:var(--color-danger)] bg-rose-50',
    accent:  'text-[color:var(--color-accent-700)] bg-[color:var(--color-accent-100)]',
    primary: 'text-[color:var(--color-brand-700)] bg-[color:var(--color-brand-100)]',
};
</script>

<template>
    <Modal :show="show" size="sm" :closable="!loading" @close="emit('close')">
        <template #header>
            <div class="flex items-center gap-3">
                <span
                    class="w-9 h-9 rounded-full flex items-center justify-center"
                    :class="iconMap[tone]"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v3.5m0 3h.01M5.07 19h13.86a2 2 0 0 0 1.74-3l-6.93-12a2 2 0 0 0-3.48 0l-6.93 12a2 2 0 0 0 1.74 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h2 class="text-base font-semibold text-[color:var(--color-ink)]">{{ title }}</h2>
            </div>
        </template>

        <p class="text-sm text-[color:var(--color-brand-700)] leading-relaxed">
            <slot>{{ message }}</slot>
        </p>

        <template #footer>
            <Button variant="ghost" :disabled="loading" @click="emit('close')">{{ cancelText }}</Button>
            <Button
                :variant="variantMap[tone]"
                :tone="toneMap[tone]"
                :loading="loading"
                @click="emit('confirm')"
            >
                {{ confirmText }}
            </Button>
        </template>
    </Modal>
</template>
