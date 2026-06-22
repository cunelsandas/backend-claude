<script setup>
defineProps({
    title:    { type: String, default: null },
    subtitle: { type: String, default: null },
    padding:  { type: String, default: 'p-6' },
    flat:     { type: Boolean, default: false },
});
</script>

<template>
    <section
        class="bg-[color:var(--color-card)] rounded-[var(--radius-md)] border border-[color:var(--color-rule)]"
        :class="!flat ? 'shadow-[var(--shadow-paper)]' : ''"
    >
        <header
            v-if="title || subtitle || $slots.header || $slots.actions"
            class="flex items-start justify-between px-6 pt-5 pb-4 border-b border-[color:var(--color-rule)]"
        >
            <div class="min-w-0">
                <slot name="header">
                    <h3 v-if="title" class="text-sm font-semibold text-[color:var(--color-ink)] tracking-tight">
                        {{ title }}
                    </h3>
                    <p v-if="subtitle" class="text-xs text-[color:var(--color-brand-500)] mt-0.5">
                        {{ subtitle }}
                    </p>
                </slot>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2 ml-4 shrink-0">
                <slot name="actions" />
            </div>
        </header>

        <div :class="padding">
            <slot />
        </div>

        <footer
            v-if="$slots.footer"
            class="px-6 py-3 border-t border-[color:var(--color-rule)] bg-[color:var(--color-brand-50)] rounded-b-[var(--radius-md)]"
        >
            <slot name="footer" />
        </footer>
    </section>
</template>
